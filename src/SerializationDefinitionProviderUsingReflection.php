<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function is_a;

class SerializationDefinitionProviderUsingReflection
{
    private KeyFormatter $keyFormatter;
    private DefaultSerializerRepository $serializers;

    public function __construct(
        DefaultSerializerRepository $serializers = null,
        KeyFormatter $keyFormatter = null,
    ) {
        $this->serializers = $serializers ?? DefaultSerializerRepository::builtIn();
        $this->keyFormatter = $keyFormatter ?? new KeyFormatterForSnakeCasing();
    }

    public function provideDefinition(string $className): ClassSerializationDefinition
    {
        $reflection = new ReflectionClass($className);
        $publicMethod = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $properties = [];

        foreach ($publicMethod as $method) {
            if ($method->isStatic() || $method->getNumberOfParameters() !== 0) {
                continue;
            }

            $methodName = $method->getShortName();
            $key = $this->keyFormatter->propertyNameToKey($methodName);
            $properties[] = new PropertySerializationDefinition(
                PropertySerializationDefinition::TYPE_METHOD,
                $methodName,
                $key,
                $this->resolveSerializer($method->getReturnType()->getName(), $method->getAttributes()),
            );
        }

        $publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($publicProperties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $key = $this->keyFormatter->propertyNameToKey($property->getName());
            $properties[] = new PropertySerializationDefinition(
                PropertySerializationDefinition::TYPE_PROPERTY,
                $property->getName(),
                $key,
                $this->resolveSerializer($property->getType()->getName(), $method->getAttributes()),
            );
        }

        return new ClassSerializationDefinition($properties);
    }

    private function resolveSerializer(string $type, array $attributes): ?array
    {
        $serializer = null;

        foreach ($attributes as $attribute) {
            $type = $attribute->getName();

            if (is_a($type, TypeSerializer::class, true)) {
                $serializer = [$attribute->getName(), $attribute->getArguments()];
                break;
            }
        }

        return $serializer ?? $this->serializers->serializerForType($type);
    }

    public function provideSerializer(string $type): ?array
    {
        return $this->serializers->serializerForType($type);
    }

    public function allSerializers(): array
    {
        return $this->serializers->allSerializersPerType();
    }
}
