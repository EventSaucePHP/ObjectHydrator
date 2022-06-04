<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

/**
 * @internal
 */
final class ClassHydrationDefinition
{
    public array $propertyDefinitions;

    public function __construct(
        public string $constructor,
        public string $constructionStyle,
        PropertyHydrationDefinition ...$propertyDefinitions,
    ) {
        $this->propertyDefinitions = $propertyDefinitions;
    }
}
