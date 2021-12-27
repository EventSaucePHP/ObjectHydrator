<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use LogicException;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

use function array_key_exists;
use function count;
use function is_array;

class ReflectionObjectHydrator implements ObjectHydrator
{
    private ?ValueConverter $propertyConverter;

    public function __construct(?ValueConverter $propertyConverter = null)
    {
        $this->propertyConverter = $propertyConverter ?: ValueConverterRegistry::default();
    }

    public function hydrateObject(string $className, array $payload): object
    {
        try {
            $reflectionClass = new ReflectionClass($className);

            $constructor = $this->resolveConstructor($reflectionClass);

            if ( ! $constructor instanceof ReflectionMethod) {
                throw new LogicException("Class $className does not have a constructor.");
            }

            $parameters = $constructor->getParameters();
            $definitions = [];

            foreach ($parameters as $parameter) {
                $paramName = $parameter->getName();
                $parameterType = $this->normalizeType($parameter->getType());
                $definition = [
                    'property' => $paramName,
                    'name' => $paramName,
                    'options' => [],
                    'enum' => $parameterType->isEnum(),
                    'type' => $parameterType,
                ];

                $attributes = $parameter->getAttributes();

                foreach ($attributes as $attribute) {
                    $attributeName = $attribute->getName();
                    $arguments = $attribute->getArguments();
                    if ($attributeName === MapFrom::class) {
                        $definition['name'] = $arguments[0] ?? $definition['name'];
                    } elseif ($attributeName === CastToType::class) {
                        /** @var CastToType $instance */
                        $instance = $attribute->newInstance();
                        $definition['cast_using'] = $instance;
                    } elseif ($attributeName === CastUsing::class) {
                        $instance = $arguments[0] ?? "ClassWasNotProvidedByAttributeClassUsingFor\\$className";
                        $options = $arguments[1] ?? [];
                        $definition['cast_using'] = new $instance;
                        $definition['options'] = $options;
                    } elseif ($attributeName === PropertyOptions::class) {
                        /** @var PropertyOptions $instance */
                        $instance = $attribute->newInstance();
                        $definition['options'] = $instance->options;
                    }
                }

                $definitions[] = $definition;
            }

            $properties = [];

            foreach ($definitions as $definition) {
                $property = $definition['property'];
                $value = $payload[$definition['name']] ?? null;

                if ($value === null) {
                    continue;
                }

                $properties[$property] = $value;

                if (array_key_exists('cast_using', $definition)) {
                    $properties[$property] = $definition['cast_using']->cast(
                        $properties[$property],
                        $definition['options'],
                        $this
                    );
                }

                /** @var PropertyType $type */
                $type = $definition['type'];
                $typeName = $type->firstTypeName();

                if ($this->propertyConverter->canConvert($typeName)) {
                    $properties[$property] = $this->propertyConverter->convert(
                        $typeName,
                        $value,
                        $definition['options']
                    );
                } elseif ($definition['enum']) {
                    $properties[$property] = $typeName::from($properties[$property]);
                } elseif ($type->canBeMapped() && is_array($value)) {
                    $properties[$property] = $this->hydrateObject($typeName, $value);
                }
            }

            if ($constructor->isConstructor() === false) {
                return $constructor->invokeArgs(null, $properties);
            }

            return new $className(...$properties);
        } catch (Throwable $exception) {
            throw UnableToHydrateObject::dueToError($className, $exception);
        }
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

    private function normalizeType(?ReflectionType $type): PropertyType
    {
        if ($type === null) {
            return PropertyType::mixed();
        }

        if ($type instanceof ReflectionNamedType) {
            return PropertyType::fromNamedType($type);
        } elseif ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            return PropertyType::fromCompositeType($type);
        }
    }
}
