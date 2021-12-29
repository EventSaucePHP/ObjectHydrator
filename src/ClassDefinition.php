<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

/**
 * @internal
 */
class ClassDefinition
{
    public array $propertyDefinitions;

    public function __construct(
        public string $constructor,
        public string $constructionStyle,
        PropertyDefinition ... $propertyDefinitions,
    )
    {
        $this->propertyDefinitions = $propertyDefinitions;
    }
}
