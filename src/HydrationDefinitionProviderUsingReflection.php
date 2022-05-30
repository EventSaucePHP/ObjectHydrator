<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use function count;
use function is_a;

final class HydrationDefinitionProviderUsingReflection implements HydrationDefinitionProvider
{
    private DefaultCasterRepository $defaultCasterRepository;
    private KeyFormatter $keyFormatter;

    public function __construct(
        DefaultCasterRepository $defaultCasterRepository = null,
        KeyFormatter $keyFormatter = null,
    ) {
        $this->defaultCasterRepository = $defaultCasterRepository ?? DefaultCasterRepository::builtIn();
        $this->keyFormatter = $keyFormatter ?? new KeyFormatterForSnakeCasing();
    }

    public function provideDefinition(string $className): ClassHydrationDefinition
    {
        $reflectionClass = new ReflectionClass($className);
        $constructor = $this->resolveConstructor($reflectionClass);

        /** @var PropertyHydrationDefinition[] $definitions */
        $definitions = [];

        $constructionStyle = $constructor instanceof ReflectionMethod ? $constructor->isConstructor(
        ) ? 'new' : 'static' : 'new';
        $constructorName = $constructionStyle === 'new' ? $className : $this->stringifyConstructor($constructor);
        $parameters = $constructor instanceof ReflectionMethod ? $constructor->getParameters() : [];

        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $key = $this->keyFormatter->propertyNameToKey($paramName);
            $parameterType = PropertyType::fromReflectionType($parameter->getType());
            $firstTypeName = $parameterType->firstTypeName();
            $keys = [$key => [$key]];

            $attributes = $parameter->getAttributes();
            $casters = [];
            $serializers = [];

            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();

                if ($attributeName === MapFrom::class) {
                    $keys = $attribute->newInstance()->keys;
                }

                if (is_a($attributeName, PropertyCaster::class, true)) {
                    $casters[] = [$attributeName, $attribute->getArguments()];
                }

                if (is_a($attributeName, PropeertySerializer::class, true)) {
                    $serializers[] = [$attributeName, $attribute->getArguments()];
                }
            }

            if ($firstTypeName && count($casters) === 0 && $defaultCaster = $this->defaultCasterRepository->casterFor(
                    $firstTypeName
                )) {
                $casters = [$defaultCaster];
            }

            $definitions[] = new PropertyHydrationDefinition(
                $keys,
                $paramName,
                $casters,
                $serializers,
                $parameterType,
                $parameterType->canBeHydrated(),
                $parameterType->isEnum(),
                $parameterType->allowsNull(),
                $firstTypeName,
            );
        }

        return new ClassHydrationDefinition($constructorName, $constructionStyle, ...$definitions);
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
