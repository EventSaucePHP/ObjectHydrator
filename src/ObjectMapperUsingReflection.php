<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use BackedEnum;
use Generator;
use Throwable;
use UnitEnum;
use function array_key_exists;
use function array_key_first;
use function array_pop;
use function constant;
use function count;
use function current;
use function get_class;
use function gettype;
use function is_a;
use function is_array;
use function is_object;
use function json_encode;

class ObjectMapperUsingReflection implements ObjectMapper
{
    private DefinitionProvider $definitionProvider;

    /** @var array<string, PropertyCaster> */
    private array $casterInstances = [];

    /** @var array<string, PropertySerializer */
    private array $serializerCache = [];

    public function __construct(
        ?DefinitionProvider $definitionProvider = null,
    ) {
        $this->definitionProvider = $definitionProvider ?? new DefinitionProvider();
    }

    /**
     * @template T
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

            $properties = [];

            foreach ($classDefinition->propertyDefinitions as $definition) {
                $value = [];

                foreach ($definition->keys as $to => $from) {
                    $p = $payload;

                    foreach ($from as $fromSegment) {
                        if ( ! is_array($p) || ! array_key_exists($fromSegment, $p)) {
                            goto next_property;
                        }
                        $p = $p[$fromSegment];
                    }

                    $value[$to] = $p;

                    next_property:
                }

                if ($value === []) {
                    continue;
                }

                if (count($definition->keys) === 1) {
                    $value = current($value);
                }

                $property = $definition->accessorName;

                foreach ($definition->casters as [$caster, $options]) {
                    $key = $className . json_encode($options);
                    /** @var PropertyCaster $propertyCaster */
                    $propertyCaster = $this->casterInstances[$key] ??= new $caster(...$options);
                    $value = $propertyCaster->cast($value, $this);
                }

                $typeName = $definition->firstTypeName;

                if ($definition->isBackedEnum()) {
                    $value = $typeName::from($value);
                } elseif ($definition->isEnum) {
                    $value = constant("$typeName::$value");
                } elseif ($definition->canBeHydrated && is_array($value)) {
                    $propertyType = $definition->propertyType;

                    if ($propertyType->isCollection()) {
                        if (is_array($value[array_key_first($value)] ?? false)) {
                            $value = $this->hydrateObjects($propertyType->firstTypeName(), $value)->toArray();
                        }
                    } else {
                        $value = $this->hydrateObject($typeName, $value);
                    }
                }

                $properties[$property] = $value;
            }

            return match ($classDefinition->constructionStyle) {
                'static' => ($classDefinition->constructor)(...$properties),
                'new' => new ($classDefinition->constructor)(...$properties),
            };
        } catch (Throwable $exception) {
            throw UnableToHydrateObject::dueToError($className, $exception);
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
        $className = get_class($object);

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

            /** @var PropertySerializationDefinition $property */
            foreach ($definition->properties as $property) {
                $keys = $property->keys;
                $accessorName = $property->accessorName;

                $value = $property->type === PropertySerializationDefinition::TYPE_METHOD
                    ? $object->{$accessorName}()
                    : $object->{$accessorName};

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
                        if (is_a($value, $valueType, false) === false && $valueType !== gettype($valueType)) {
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
                    $value = $this->serializeObject($value);
                }

                $this->assignToResult($keys, $result, $value);
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
     * @template T
     *
     * @param class-string<T> $className
     * @param iterable<array> $payloads;
     *
     * @return IterableList<T>
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
}
