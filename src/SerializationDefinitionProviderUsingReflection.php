<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

use ReflectionUnionType;

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
            $returnType = $method->getReturnType();
            $attributes = $method->getAttributes();
            $properties[] = new PropertySerializationDefinition(
                PropertySerializationDefinition::TYPE_METHOD,
                $methodName,
                $this->resolveSerializers($returnType, $attributes),
                PropertyType::fromReflectionType($returnType),
                $returnType->allowsNull(),
                $this->resolveKeys($key, $attributes),
            );
        }

        $publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($publicProperties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $key = $this->keyFormatter->propertyNameToKey($property->getName());
            $propertyType = $property->getType();
            $attributes = $property->getAttributes();
            $properties[] = new PropertySerializationDefinition(
                PropertySerializationDefinition::TYPE_PROPERTY,
                $property->getName(),
                $this->resolveSerializers($propertyType, $attributes),
                PropertyType::fromReflectionType($propertyType),
                $propertyType->allowsNull(),
                $this->resolveKeys($key, $attributes),
            );
        }

        return new ClassSerializationDefinition($properties);
    }

    private function resolveSerializer(string $type, array $attributes): ?array
    {
        $serializer = $this->resolveSerializerFromAttributes($attributes);

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

    private function resolveSerializers(ReflectionUnionType|ReflectionNamedType $type, array $attributes): array
    {
        $attributeSerializer = $this->resolveSerializerFromAttributes($attributes);

        if ($attributeSerializer !== null) {
            return [$attributeSerializer];
        }

        if ($type instanceof ReflectionNamedType) {
            return [$this->serializers->serializerForType($type->getName())];
        }

        $serializersPerType = [];

        foreach ($type->getTypes() as $t) {
            $typeName = $t->getName();
            $serializersPerType[$typeName] = $this->resolveSerializer($typeName, $attributes);
        }

        return $serializersPerType;
    }

    /**
     * @param ReflectionAttribute[] $attributes
     */
    private function resolveSerializerFromAttributes(array $attributes): ?array
    {
        foreach ($attributes as $attribute) {
            $name = $attribute->getName();

            if (is_a($name, TypeSerializer::class, true)) {
                return [$attribute->getName(), $attribute->getArguments()];
            }
        }

        return null;
    }

    public function hasSerializerFor(string $name): bool
    {
        return $this->serializers->serializerForType($name) !== null;
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
}
