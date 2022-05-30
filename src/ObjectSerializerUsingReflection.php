<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use BackedEnum;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;

use ReflectionProperty;

use ReflectionUnionType;

use Throwable;
use UnitEnum;

use function array_key_exists;
use function array_pop;
use function array_reverse;
use function assert;
use function count;
use function enum_exists;
use function function_exists;
use function get_class;
use function is_a;
use function is_array;
use function is_object;
use function var_dump;

class ObjectSerializerUsingReflection implements ObjectSerializer
{
    private DefaultSerializerRepository $serializers;
    private KeyFormatter $keyFormatter;

    public function __construct(
        DefaultSerializerRepository $serializers = null,
        KeyFormatter $keyFormatter = null,
    ) {
        $this->serializers = $serializers ?? DefaultSerializerRepository::builtIn();
        $this->keyFormatter = $keyFormatter ?? new KeyFormatterForSnakeCasing();
    }

    public function serializeObject(object $object): mixed
    {
        $result = [];
        $className = get_class($object);

        try {
            if ($serializer = $this->serializers->serializerForType($className)) {
                [$serializerClass, $arguments] = $serializer;

                return (new $serializerClass(...$arguments))->serialize($object, $this);
            }

            $reflection = new ReflectionClass($className);
            $publicMethod = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($publicMethod as $method) {
                if ($method->isStatic() || $method->getNumberOfParameters() !== 0) {
                    continue;
                }

                $methodName = $method->getShortName();
                $returnType = $method->getReturnType();
                $key = $this->keyFormatter->propertyNameToKey($methodName);
                $value = $method->invoke($object);
                $attributes = $method->getAttributes();
                $value = $this->serializeValue(
                    $returnType->getName(),
                    $returnType->isBuiltin(),
                    $value,
                    $attributes
                );

                $this->assignToResult($key, $attributes, $result, $value);
            }

            $publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($publicProperties as $property) {
                if ($property->isStatic()) {
                    continue;
                }

                $key = $this->keyFormatter->propertyNameToKey($property->getName());
                $value = $property->getValue($object);
                $propertyType = $property->getType();
                $attributes = $property->getAttributes();

                if ($propertyType instanceof ReflectionUnionType) {
                    foreach ($propertyType->getTypes() as $namedType) {
                        if (is_a($value, $namedType->getName())) {
                            $value = $this->serializeValue(
                                $namedType->getName(),
                                $namedType->isBuiltin(),
                                $value,
                                $attributes
                            );
                        }
                    }
                } else {
                    $value = $this->serializeValue(
                        $propertyType->getName(),
                        $propertyType->isBuiltin(),
                        $value,
                        $attributes
                    );
                }

                $this->assignToResult($key, $attributes, $result, $value);
            }

            return $result;
        } catch (Throwable $throwable) {
            throw UnableToSerializeObject::dueToError($className, $throwable);
        }
    }

    /**
     * @param ReflectionAttribute[] $attributes
     */
    private function serializeValue(string $type, bool $builtIn, mixed $value, array $attributes): mixed
    {
        $serializers = [];

        foreach ($attributes as $attribute) {
            $t = $attribute->getName();

            if (is_a($t, PropertySerializer::class, true)) {
                $serializers[] = [$attribute->getName(), $attribute->getArguments()];
            }
        }

        $serializers = array_reverse($serializers, true);

        if (count($serializers) === 0 && $default = $this->serializers->serializerForType($type)) {
            $serializers[] = $default;
        }

        if (count($serializers) !== 0) {
            foreach ($serializers as $serializer) {
                [$serializerClass, $arguments] = $serializer;
                /** @var PropertySerializer $serializer */
                $serializer = new $serializerClass(...$arguments);
                $value = $serializer->serialize($value, $this);
            }
        } elseif ( ! $builtIn) {
            if ($value instanceof BackedEnum) {
                return $value->value;
            } elseif ($value instanceof UnitEnum) {
                return $value->name;
            } elseif (is_object($value)) {
                return $this->serializeObject($value);
            }
        }

        return $value;
    }

    /**
     * @param ReflectionAttribute[] $attributes
     *
     * @return array<string, array<string>>
     */
    private function resolveKeys(string $defaultKey, array $attributes): array
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === MapFrom::class) {
                /** @var MapFrom $mapFrom */
                $mapFrom = $attribute->newInstance();

                return $mapFrom->keys;
            }
        }

        return [$defaultKey => [$defaultKey]];
    }

    private function assignToResult(string $key, array $attributes, array &$result, mixed $value): void
    {
        $keys = $this->resolveKeys($key, $attributes);
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
