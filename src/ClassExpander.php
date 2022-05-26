<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionClass;
use Throwable;
use function array_key_exists;
use function array_values;
use function enum_exists;
use function function_exists;
use function in_array;

/**
 * @internal
 */
final class ClassExpander
{
    private function __construct()
    {
    }

    public static function expandClassesForHydration(array $classes, HydrationDefinitionProvider $definitionProvider): array
    {
        $classes = array_values($classes);

        for ($i = 0; array_key_exists($i, $classes); ++$i) {
            $class = $classes[$i];
            $classDefinition = $definitionProvider->provideDefinition($class);

            foreach ($classDefinition->propertyDefinitions as $propertyDefinition) {
                if ($propertyDefinition->canBeHydrated === false) {
                    continue;
                }

                $className = (string) $propertyDefinition->concreteTypeName;

                if ( ! in_array($className, $classes) && static::isClass($className)) {
                    $classes[] = $className;
                }
            }
        }

        return $classes;
    }

    private static function isClass(string $className): bool
    {
        if (function_exists('enum_exists') && enum_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);

            return $reflection->isInstantiable();
        } catch (Throwable) {
            return false;
        }
    }

    public static function expandClassesForSerialization(
        array $classes,
        SerializationDefinitionProviderUsingReflection $definitionProvider
    ): array {
        $classes = array_values($classes);

        for ($i = 0; array_key_exists($i, $classes); ++$i) {
            $class = $classes[$i];
            $classDefinition = $definitionProvider->provideDefinition($class);

            /** @var PropertySerializationDefinition $property */
            foreach ($classDefinition->properties as $property) {
                $type = $property->type;

                if ( ! in_array($type, $classes) && self::isClass($type)) {
                    $classes[] = $type;
                }
            }
        }

        return $classes;
    }
}
