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

final class ReflectionDefinitionProvider implements DefinitionProvider
{
    private DefaultCasterRepository $defaultCasterRepository;
    private KeyFormatter $keyFormatter;

    public function __construct(
        DefaultCasterRepository $defaultCasterRepository = null,
        KeyFormatter $keyFormatter = null,
    ) {
        $this->defaultCasterRepository = $defaultCasterRepository ?? DefaultCasterRepository::buildIn();
        $this->keyFormatter = $keyFormatter ?? new KeyFormattingWithoutConversion();
    }

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
            $key = $this->keyFormatter->formatPropertyName($paramName);
            $parameterType = $this->normalizeType($parameter->getType());
            $firstTypeName = $parameterType->firstTypeName();
            $definition = [
                'property' => $paramName,
                'keys' => [$key => [$key]],
                'enum' => $parameterType->isEnum(),
            ];

            $attributes = $parameter->getAttributes();
            $casters = [];

            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();

                if ($attributeName === MapFrom::class) {
                    $definition['keys'] = $attribute->newInstance()->keys;
                } elseif (is_a($attributeName, PropertyCaster::class, true)) {
                    $casters[] = [$attributeName, $attribute->getArguments()];
                }
            }

            if ($firstTypeName && count($casters) === 0 && $defaultCaster = $this->defaultCasterRepository->casterFor(
                    $firstTypeName
                )) {
                $casters = [$defaultCaster];
            }

            $definitions[] = new PropertyDefinition(
                $definition['keys'],
                $definition['property'],
                $casters,
                $parameterType->canBeHydrated(),
                $definition['enum'] ?? false,
                $firstTypeName
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
