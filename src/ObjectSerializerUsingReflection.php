<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use BackedEnum;
use Throwable;
use UnitEnum;

use function array_keys;
use function array_pop;
use function count;
use function get_class;
use function gettype;
use function is_a;
use function is_object;
use function json_encode;

class ObjectSerializerUsingReflection implements ObjectSerializer
{
    /** @var array<string, PropertySerializer */
    private array $serializerCache = [];
    private SerializationDefinitionProvider $serializationProvider;

    public function __construct(
        SerializationDefinitionProvider $definitionProvider = null,
    ) {
        $this->serializationProvider = $definitionProvider ?? new SerializationDefinitionProvider();
    }

    public function serializeObject(object $object): mixed
    {
        $className = get_class($object);

        try {
            if ($serializer = $this->serializationProvider->provideSerializer($className)) {
                /** @var class-string<PropertySerializer> $serializerClass */
                [$serializerClass, $arguments] = $serializer;
                $cacheKey = $serializerClass . json_encode($arguments);
                $this->serializerCache[$cacheKey] ??= new $serializerClass(...$arguments);

                return $this->serializerCache[$cacheKey]->serialize($object, $this);
            }

            $result = [];
            $definition = $this->serializationProvider->provideDefinition($className);

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

                if (array_keys($serializers)[0] === 0) {
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
}
