<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use BackedEnum;
use Generator;
use LogicException;
use Throwable;
use UnitEnum;
use function array_key_exists;
use function array_key_first;
use function array_pop;
use function constant;
use function count;
use function current;
use function end;
use function get_class;
use function gettype;
use function implode;
use function is_a;
use function is_array;
use function is_object;
use function json_encode;

class ObjectMapperUsingReflection implements ObjectMapper
{
    private DefinitionProvider $definitionProvider;

    /** @var array<string, PropertyCaster> */
    private array $casterInstances = [];

    /** @var array<string, PropertySerializer> */
    private array $serializerCache = [];

    /** @var string[] */
    private array $hydrationStack = [];

    public function __construct(
        ?DefinitionProvider $definitionProvider = null,
    ) {
        $this->definitionProvider = $definitionProvider ?? new DefinitionProvider();
    }

    private function extractPayloadViaMap(array $payload, array $inputMap): mixed
    {
        $newPayload = [];

        foreach ($inputMap as $to => $from) {
            $p = $payload;
            $newPayload[$to] = null;

            foreach ($from as $fromSegment) {
                if ( ! is_array($p) || ! array_key_exists($fromSegment, $p)) {
                    goto next_property;
                }
                $p = $p[$fromSegment];
            }

            $newPayload[$to] = $p;

            next_property:
        }

        return count($inputMap) === 1
            ? current($newPayload)
            : $newPayload;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws UnableToHydrateObject
     */
    public function hydrateObject(string $className, array $payload): object
    {
        try {
            $classDefinition = $this->definitionProvider->provideHydrationDefinition($className);

            if ($classDefinition->mapFrom) {
                $payload = $this->extractPayloadViaMap($payload, $classDefinition->mapFrom);
            }

            if ($classDefinition->typeKey) {
                return $this->hydrateViaTypeMap($classDefinition, $payload);
            }

            $properties = [];
            $missingFields = [];

            foreach ($classDefinition->propertyDefinitions as $definition) {
                $keys = $definition->keys;
                $property = $definition->accessorName;
                $value = $this->extractPayloadViaMap($payload, $keys);

                foreach ($definition->casters as [$caster, $options]) {
                    $key = $className . '-' . $caster . '-' . json_encode($options);
                    /** @var PropertyCaster $propertyCaster */
                    $propertyCaster = $this->casterInstances[$key] ??= new $caster(...$options);
                    $value = $propertyCaster->cast($value, $this);
                }

                if ($value === null) {
                    if ($definition->hasDefaultValue) {
                        continue;
                    } elseif ($definition->nullable) {
                        $properties[$property] = null;
                    } else {
                        $missingFields[] = implode('.', end($keys));
                    }
                    continue;
                }

                if ($definition->typeKey && is_array($value)) {
                    $value = $this->hydrateViaTypeMap($definition, $value);
                }

                $typeName = $definition->firstTypeName;

                if ($definition->isBackedEnum()) {
                    $value = $typeName::from($value);
                } elseif ($definition->isEnum) {
                    $value = constant("$typeName::$value");
                } elseif ($definition->canBeHydrated && is_array($value)) {
                    $propertyType = $definition->propertyType;

                    try {
                        $this->hydrationStack[] = $property;

                        if ($propertyType->isCollection()) {
                            if (is_array($value[array_key_first($value)] ?? false)) {
                                $value = $this->hydrateObjects($propertyType->firstTypeName(), $value)->toArray();
                            }
                        } else {
                            $value = $this->hydrateObject($typeName, $value);
                        }
                    } finally {
                        array_pop($this->hydrationStack);
                    }
                }

                set_value:
                $properties[$property] = $value;
            }
        } catch (Throwable $exception) {
            throw UnableToHydrateObject::dueToError($className, $exception, $this->hydrationStack);
        }

        if (count($missingFields) > 0) {
            throw UnableToHydrateObject::dueToMissingFields($className, $missingFields, $this->hydrationStack);
        }

        try {
            return match ($classDefinition->constructionStyle) {
                'static' => ($classDefinition->constructor)(...$properties),
                'new' => new ($classDefinition->constructor)(...$properties),
            };
        } catch (Throwable $exception) {
            throw UnableToHydrateObject::dueToError($className, $exception, $this->hydrationStack);
        }
    }

    /**
     * @template T
     *
     * @param class-string<T> $className
     * @param iterable<array> $payloads;
     *
     * @return IterableList<T>
     *
     * @throws UnableToHydrateObject
     */
    public function hydrateObjects(string $className, iterable $payloads): IterableList
    {
        return new IterableList($this->doHydrateObjects($className, $payloads));
    }

    private function doHydrateObjects(string $className, iterable $payloads): Generator
    {
        foreach ($payloads as $index => $payload) {
            yield $index => $this->hydrateObject($className, $payload);
        }
    }

    public function serializeObject(object $object): mixed
    {
        return $this->serializeObjectOfType($object, get_class($object));
    }

    /**
     * @template T
     *
     * @param T               $object
     * @param class-string<T> $className
     */
    public function serializeObjectOfType(object $object, string $className): mixed
    {
        try {
            if ($serializer = $this->definitionProvider->provideSerializer($className)) {
                /** @var class-string<PropertySerializer> $serializerClass */
                [$serializerClass, $arguments] = $serializer;
                $cacheKey = $serializerClass . json_encode($arguments);
                $this->serializerCache[$cacheKey] ??= new $serializerClass(...$arguments);

                return $this->serializerCache[$cacheKey]->serialize($object, $this);
            }

            $result = [];
            $definition = $this->definitionProvider->provideSerializationDefinition($className);

            if ($definition->typeKey) {
                foreach ($definition->typeMap as $payloadType => $valueType) {
                    if (is_a($object, $valueType)) {
                        $result = $this->serializeObjectOfType($object, $valueType);
                        $result[$definition->typeKey] = $payloadType;

                        goto process_map_from;
                    }
                }

                throw new LogicException('Unable to map object to type for value type: ' . get_class($object));
            }

            /** @var PropertySerializationDefinition $property */
            foreach ($definition->properties as $property) {
                $defaults = [];
                $keys = $property->keys;
                $accessorName = $property->accessorName;

                $value = $property->type === PropertySerializationDefinition::TYPE_METHOD
                    ? $object->{$accessorName}()
                    : $object->{$accessorName};

                if ($value !== null && $property->typeSpecifier) {
                    foreach ($property->typeMap as $payloadType => $valueType) {
                        if (is_a($value, $valueType)) {
                            $defaults[$property->typeSpecifier] = $payloadType;
                        }
                    }
                }

                if ($value === null || count($property->serializers) === 0) {
                    goto assign_result;
                }

                $serializers = $property->serializers;

                if (array_key_first($serializers) === 0) {
                    foreach ($serializers as $serializer) {
                        /** @var class-string<PropertySerializer> $serializerClass */
                        [$serializerClass, $arguments] = $serializer;
                        $cacheKey = $serializerClass . json_encode($arguments);
                        $this->serializerCache[$cacheKey] ??= new $serializerClass(...$arguments);

                        $value = $this->serializerCache[$cacheKey]->serialize($value, $this);
                    }
                } else {
                    foreach ($serializers as $valueType => $serializer) {
                        if (is_a($value, $valueType) === false && $valueType !== gettype($valueType)) {
                            continue;
                        }

                        /** @var class-string<PropertySerializer> $serializerClass */
                        [$serializerClass, $arguments] = $serializer;
                        $cacheKey = $serializerClass . json_encode($arguments);
                        $this->serializerCache[$cacheKey] ??= new $serializerClass(...$arguments);
                        $value = $this->serializerCache[$cacheKey]->serialize($value, $this);
                    }
                }

                assign_result:

                if ($value instanceof BackedEnum) {
                    $value = $value->value;
                } elseif ($value instanceof UnitEnum) {
                    $value = $value->name;
                } elseif (is_object($value)) {
                    $value = $defaults + $this->serializeObject($value);
                }

                $this->assignToResult($keys, $result, $value);
            }

            process_map_from:
            if ($mapFrom = $definition->mapFrom) {
                $r = [];
                $this->assignToResult($mapFrom, $r, $result);

                return $r;
            }

            return $result;
        } catch (Throwable $throwable) {
            throw UnableToSerializeObject::dueToError($className, $throwable);
        }
    }

    private function assignToResult(array $keys, array &$result, mixed $value): void
    {
        $mapFromSingleKey = count($keys) === 1;

        foreach ($keys as $payloadKey => $toPath) {
            $lastKey = array_pop($toPath);
            $r = &$result;

            foreach ($toPath as $to) {
                $r[$to] ??= [];
                $r = &$r[$to];
            }

            $r[$lastKey] = $mapFromSingleKey ? $value : $value[$payloadKey];
        }
    }

    /**
     * @param iterable<object> $payloads
     *
     * @return IterableList<array<mixed>>
     *
     * @throws UnableToSerializeObject
     */
    public function serializeObjects(iterable $payloads): IterableList
    {
        return new IterableList($this->doSerializeObjects($payloads));
    }

    private function doSerializeObjects(iterable $objects): Generator
    {
        foreach ($objects as $index => $object) {
            yield $index => $this->serializeObject($object);
        }
    }

    private function hydrateViaTypeMap(PropertyHydrationDefinition|ClassHydrationDefinition $definition, array $payload): object
    {
        $type = $payload[$definition->typeKey ?? ''] ?? '';
        $valueType = $definition->typeMap[$type] ?? null;

        if ($valueType === null) {
            throw new LogicException("No type mapped for serialized type \"$type\"");
        }

        return $this->hydrateObject($valueType, $payload);
    }
}
