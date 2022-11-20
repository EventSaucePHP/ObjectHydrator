<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use function array_key_exists;
use function array_unique;
use function array_values;
use function class_exists;
use function in_array;
use function interface_exists;

/**
 * @internal
 */
final class ClassExpander
{
    private function __construct()
    {
    }

    /**
     * @param array<class-string> $classes
     *
     * @return array<class-string>
     */
    public static function expandClassesForHydration(array $classes, DefinitionProvider $definitionProvider): array
    {
        $classes = array_unique(array_values($classes));

        for ($i = 0; array_key_exists($i, $classes); ++$i) {
            $class = $classes[$i];
            $classDefinition = $definitionProvider->provideHydrationDefinition($class);

            foreach ($classDefinition->propertyDefinitions as $propertyDefinition) {
                if ($propertyDefinition->canBeHydrated === false) {
                    continue;
                }

                $className = (string) $propertyDefinition->firstTypeName;

                if ( ! in_array($className, $classes) && static::isClass($className)) {
                    $classes[] = $className;
                }
            }
        }

        return $classes;
    }

    private static function isClass(string $className): bool
    {
        return class_exists($className) || interface_exists($className);
    }

    /**
     * @param array<class-string> $classes
     *
     * @return array<class-string>
     */
    public static function expandClassesForSerialization(
        array $classes,
        DefinitionProvider $definitionProvider
    ): array {
        $classes = array_values($classes);

        for ($i = 0; array_key_exists($i, $classes); ++$i) {
            $class = $classes[$i];
            $classDefinition = $definitionProvider->provideSerializationDefinition($class);

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
