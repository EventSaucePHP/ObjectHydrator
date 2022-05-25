<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionAttribute;
use ReflectionMethod;
use ReflectionObject;

use ReflectionProperty;

use function is_a;
use function is_scalar;

class ObjectSerializerUsingReflection implements ObjectSerializer
{
    private ?DefaultSerializerRepository $serializers;
    private KeyFormatter $keyFormatter;

    public function __construct(
        DefaultSerializerRepository $serializers = null,
        KeyFormatter $keyFormatter = null,
    ) {
        $this->serializers = $serializers ?? DefaultSerializerRepository::builtIn();
        $this->keyFormatter = $keyFormatter ?? new KeyFormatterForSnakeCasing();
    }

    public function serializeObject(object $object): array
    {
        $result = [];
        $reflection = new ReflectionObject($object);
        $publicMethod = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($publicMethod as $method) {
            if ($method->isStatic() || $method->getNumberOfParameters() !== 0) {
                continue;
            }

            $methodName = $method->getShortName();
            $key = $this->keyFormatter->propertyNameToKey($methodName);
            $value = $method->invoke($object);
            $value = $this->serializeValue($method->getReturnType()->getName(), $value, $method->getAttributes());
            $result[$key] = $value;
        }

        $publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($publicProperties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $key = $this->keyFormatter->propertyNameToKey($property->getName());
            $value = $property->getValue($object);
            $value = $this->serializeValue($property->getType()->getName(), $value, $property->getAttributes());
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param ReflectionAttribute[] $attributes
     */
    private function serializeValue(string $type, mixed $value, array $attributes): mixed
    {
        $serializer = null;

        foreach ($attributes as $attribute) {
            $type = $attribute->getName();

            if (is_a($type, TypeSerializer::class, true)) {
                $serializer = [$attribute->getName(), $attribute->getArguments()];
                break;
            }
        }

        $serializer ??= $this->serializers->serializerForType($type);

        if ($serializer !== null) {
            [$serializerClass, $arguments] = $serializer;
            /** @var TypeSerializer $serializer */
            $serializer = new $serializerClass(...$arguments);
            $value = $serializer->serialize($value, $this);
        }

        return $value;
    }
}
