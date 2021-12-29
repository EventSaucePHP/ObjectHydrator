<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionType;
use ReflectionUnionType;

use function count;
use function is_a;
use function var_dump;

class ReflectionDefinitionProvider implements DefinitionProvider
{
    public function provideDefinition(string $className): ClassDefinition
    {
        $reflectionClass = new ReflectionClass($className);
        $constructor = $this->resolveConstructor($reflectionClass);

        /** @var PropertyDefinition[] $definitions */
        $definitions = [];

        $constructionStyle = $constructor instanceof ReflectionMethod ? $constructor->isConstructor(
        ) ? 'new' : 'static' : 'new';
        $constructorName = $constructionStyle === 'new' ? $className : $this->stringifyConstructor($constructor);
        $parameters = $constructor instanceof ReflectionMethod ? $constructor->getParameters() : [];

        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $parameterType = $this->normalizeType($parameter->getType());
            $definition = [
                'property' => $paramName,
                'key' => $paramName,
                'enum' => $parameterType->isEnum(),
            ];

            $attributes = $parameter->getAttributes();

            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();
                $arguments = $attribute->getArguments();
                if ($attributeName === MapFrom::class) {
                    $definition['key'] = $arguments[0] ?? $definition['key'];
                } elseif (is_a($attributeName, PropertyCaster::class, true)) {
                    $definition['cast_using'] = $attributeName;
                    $definition['casting_options'] = $attribute->getArguments();
                }
            }

            $definitions[] = new PropertyDefinition(
                $definition['key'],
                $definition['property'],
                $definition['cast_using'] ?? null,
                $definition['casting_options'] ?? [],
                $parameterType->canBeHydrated(),
                $definition['enum'] ?? false,
                $parameterType->firstTypeName()
            );
        }

        return new ClassDefinition($constructorName, $constructionStyle, ...$definitions);
    }

    private function normalizeType(?ReflectionType $type): PropertyType
    {
        if ($type === null) {
            return PropertyType::mixed();
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            return PropertyType::fromCompositeType($type);
        }

        return PropertyType::fromNamedType($type);
    }

    private function resolveConstructor(ReflectionClass $reflectionClass): ?ReflectionMethod
    {
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $isConstructor = $method->getAttributes(Constructor::class);

            if (count($isConstructor) !== 0) {
                return $method;
            }
        }

        return $reflectionClass->getConstructor();
    }

    private function stringifyConstructor(ReflectionMethod $constructor): string
    {
        $name = $constructor->getName();
        $className = $constructor->getDeclaringClass()->getName();

        return "$className::$name";
    }
}
