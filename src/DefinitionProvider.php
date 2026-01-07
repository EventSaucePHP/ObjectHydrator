<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use function array_key_exists;
use function array_reverse;
use function count;
use function is_a;

final class DefinitionProvider
{
    /** @var array<class-string, ClassHydrationDefinition> */
    private array $definitionCache = [];
    private DefaultCasterRepository $defaultCasters;
    private KeyFormatter $keyFormatter;
    private DefaultSerializerRepository $defaultSerializers;
    private PropertyTypeResolver $propertyTypeResolver;
    private bool $serializePublicMethods;
    private ConstructorResolver $constructorResolver;

    public function __construct(
        ?DefaultCasterRepository     $defaultCasterRepository = null,
        ?KeyFormatter                $keyFormatter = null,
        ?DefaultSerializerRepository $defaultSerializerRepository = null,
        ?PropertyTypeResolver        $propertyTypeResolver = null,
        bool                         $serializePublicMethods = true,
        ?ConstructorResolver         $constructorResolver = null,
    )
    {
        $this->defaultCasters = $defaultCasterRepository ?? DefaultCasterRepository::builtIn();
        $this->keyFormatter = $keyFormatter ?? new KeyFormatterForSnakeCasing();
        $this->defaultSerializers = $defaultSerializerRepository ?? DefaultSerializerRepository::builtIn();
        $this->propertyTypeResolver = $propertyTypeResolver ?? new NaivePropertyTypeResolver();
        $this->serializePublicMethods = $serializePublicMethods;
        $this->constructorResolver = $constructorResolver ?? new AttributeConstructorResolver();
    }

    /**
     * BC method.
     *
     * @deprecated
     */
    public function provideDefinition(string $className): ClassHydrationDefinition
    {
        return $this->provideHydrationDefinition($className);
    }

    public function provideHydrationDefinition(string $className): ClassHydrationDefinition
    {
        if (array_key_exists($className, $this->definitionCache)) {
            return $this->definitionCache[$className];
        }

        $reflectionClass = new ReflectionClass($className);
        $constructor = $this->constructorResolver->resolveConstructor($reflectionClass);
        $classAttributes = $reflectionClass->getAttributes();

        /** @var PropertyHydrationDefinition[] $definitions */
        $definitions = [];

        $constructionStyle = match (true) {
            $constructor instanceof ReflectionMethod => $constructor->isConstructor() ? 'new' : 'static',
            ! $reflectionClass->isInstantiable() => 'none',
            default => 'new',
        };

        if ($constructionStyle !== 'none') {
            $constructorName = $constructionStyle === 'new' ? $className : $this->stringifyConstructor($constructor);
        } else {
            $constructorName = '';
        }

        /** @var ReflectionParameter[] $parameters */
        $parameters = $constructor instanceof ReflectionMethod ? $constructor->getParameters() : [];

        foreach ($parameters as $parameter) {
            $accessorName = $parameter->getName();
            $key = $this->keyFormatter->propertyNameToKey($accessorName);
            $parameterType = $this->propertyTypeResolver->typeFromConstructorParameter($parameter, $constructor);
            $firstTypeName = $parameterType->firstTypeName();
            $keys = [$key => [$key]];
            $attributes = $parameter->getAttributes();
            $casters = [];

            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();

                if ($attributeName === MapFrom::class) {
                    $keys = $attribute->newInstance()->keys;
                }

                if (is_a($attributeName, PropertyCaster::class, true)) {
                    $casters[] = [$attributeName, $attribute->getArguments()];
                }
            }

            if ($firstTypeName && count($casters) === 0 && $defaultCaster = $this->defaultCasters->casterFor(
                    $firstTypeName
                )) {
                $casters = [$defaultCaster];
            }

            $typeSpecifier = $this->typeSpecifier($attributes);
            $definitions[] = new PropertyHydrationDefinition(
                $keys,
                $accessorName,
                $casters,
                $parameterType,
                $parameterType->canBeHydrated(),
                $parameterType->isEnum(),
                $parameterType->allowsNull(),
                $parameter->isDefaultValueAvailable(),
                $firstTypeName,
                $typeSpecifier?->key,
                $typeSpecifier?->map ?: [],
            );
        }

        $typeSpecifier = $this->typeSpecifier($classAttributes);
        $mapFrom = $this->resolveMapFrom($classAttributes);

        return $this->definitionCache[$className] = new ClassHydrationDefinition(
            $constructorName,
            $constructionStyle,
            $typeSpecifier?->key,
            $typeSpecifier?->map ?: [],
            $mapFrom,
            ...$definitions,
        );
    }

    private function stringifyConstructor(ReflectionMethod $constructor): string
    {
        return $constructor->getDeclaringClass()->getName() . '::' . $constructor->getName();
    }

    public function provideSerializationDefinition(string $className): ClassSerializationDefinition
    {
        $reflection = new ReflectionClass($className);
        $constructor = $this->constructorResolver->resolveConstructor($reflection);
        $objectSettings = $this->resolveObjectSettings($reflection);
        $classAttributes = $reflection->getAttributes();
        $properties = [];
        $publicMethods = [];

        if ($this->serializePublicMethods) {
            $publicMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        }

        foreach ($publicMethods as $method) {
            if ($objectSettings->serializePublicMethods === false
                || $method->isStatic()
                || $method->getNumberOfParameters() !== 0
                || count($method->getAttributes(DoNotSerialize::class)) === 1
                || $method->getReturnType() === null) {
                continue;
            }

            $methodName = $method->getShortName();
            $key = $this->keyFormatter->propertyNameToKey($methodName);
            /** @var ReflectionNamedType|ReflectionUnionType $returnType */
            $returnType = $method->getReturnType();
            $attributes = $method->getAttributes();
            $typeSpecifier = $this->typeSpecifier($attributes);
            $properties[] = new PropertySerializationDefinition(
                PropertySerializationDefinition::TYPE_METHOD,
                $methodName,
                $this->resolveSerializers($returnType, $attributes),
                PropertyType::fromReflectionType($returnType),
                $returnType->allowsNull(),
                $this->resolveKeys($key, $attributes),
                $typeSpecifier?->key,
                $typeSpecifier?->map ?: [],
            );
        }

        $publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($publicProperties as $property) {
            if ($property->isStatic()
                || $objectSettings->serializePublicProperties === false
                || count($property->getAttributes(DoNotSerialize::class)) === 1) {
                continue;
            }

            $key = $this->keyFormatter->propertyNameToKey($property->getName());

            if ($property->isPromoted()) {
                $propertyType = $this->propertyTypeResolver->typeFromConstructorParameter($property, $constructor);
            } else {
                $propertyType = $property->getType();
            }

            $attributes = $property->getAttributes();
            $serializers = $this->resolveSerializers($property->getType(), $attributes);

            if ($property->isPromoted()) {
                $serializers = array_reverse($serializers);
            }

            $typeSpecifier = $this->typeSpecifier($attributes);
            $properties[] = new PropertySerializationDefinition(
                PropertySerializationDefinition::TYPE_PROPERTY,
                $property->getName(),
                $serializers,
                $propertyType instanceof PropertyType ? $propertyType : PropertyType::fromReflectionType($propertyType),
                $propertyType->allowsNull(),
                $this->resolveKeys($key, $attributes),
                $typeSpecifier?->key,
                $typeSpecifier?->map ?: [],
            );
        }

        $typeSpecifier = $this->typeSpecifier($reflection->getAttributes());

        return new ClassSerializationDefinition(
            $properties,
            $typeSpecifier?->key,
            $typeSpecifier?->map ?: [],
            $this->resolveMapFrom($classAttributes),
        );
    }

    /**
     * @param ReflectionAttribute[] $attributes
     */
    private function typeSpecifier(array $attributes): ?MapToType
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() == MapToType::class) {
                return $attribute->newInstance();
            }
        }

        return null;
    }

    private function resolveSerializer(string $type, array $attributes): array
    {
        $serializers = $this->resolveSerializersFromAttributes($attributes);

        if (count($serializers) === 0 && $default = $this->defaultSerializers->serializerForType($type)) {
            $serializers[] = $default;
        }

        return $serializers;
    }

    public function provideSerializer(string $type): ?array
    {
        return $this->defaultSerializers->serializerForType($type);
    }

    public function allSerializers(): array
    {
        return $this->defaultSerializers->allSerializersPerType();
    }

    /**
     * @param ReflectionAttribute[] $attributes
     *
     * @return array<string, array{0: class-string<PropertySerializer>, 1: array<mixed>}>|array<array{0: class-string<PropertySerializer>, 1: array<mixed>}>
     */
    private function resolveSerializers(ReflectionUnionType|ReflectionNamedType $type, array $attributes): array
    {
        $attributeSerializer = $this->resolveSerializersFromAttributes($attributes);

        if (count($attributeSerializer) !== 0) {
            return $attributeSerializer;
        }

        if ($type instanceof ReflectionNamedType) {
            return [$this->defaultSerializers->serializerForType($type->getName())];
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
    private function resolveSerializersFromAttributes(array $attributes): array
    {
        $serializers = [];

        foreach ($attributes as $attribute) {
            $name = $attribute->getName();

            if (is_a($name, PropertySerializer::class, true)) {
                $serializers[] = [$attribute->getName(), $attribute->getArguments()];
            }
        }

        return $serializers;
    }

    public function hasSerializerFor(string $name): bool
    {
        return $this->defaultSerializers->serializerForType($name) !== null;
    }

    /**
     * @param ReflectionAttribute[] $attributes
     *
     * @return array<string, array<string>>
     */
    private function resolveKeys(string $defaultKey, array $attributes): array
    {
        return $this->resolveMapFrom($attributes) ?: [$defaultKey => [$defaultKey]];
    }

    /**
     * @param ReflectionAttribute[] $attributes
     */
    private function resolveMapFrom(array $attributes): array|false
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === MapFrom::class) {
                /** @var MapFrom $mapFrom */
                $mapFrom = $attribute->newInstance();

                return $mapFrom->keys;
            }
        }

        return false;
    }

    private function resolveObjectSettings(ReflectionClass $reflection): MapperSettings
    {
        $attributes = $this->getMapperAttributes($reflection);

        if ($attributes) {
            return $attributes[0]->newInstance();
        }

        return new MapperSettings();
    }

    /**
     * @return ReflectionAttribute[]
     */
    private function getMapperAttributes(ReflectionClass $reflection): array
    {
        $attributes = $reflection->getAttributes(MapperSettings::class);

        if ($attributes) {
            return $attributes;
        }

        foreach ($reflection->getInterfaces() as $reflection) {
            $attributes = $reflection->getAttributes(MapperSettings::class);

            if ($attributes) {
                return $attributes;
            }
        }

        return [];
    }
}
