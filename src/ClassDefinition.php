<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ClassDefinition
{
    public readonly array $propertyDefinitions;

    public function __construct(
        public readonly string $constructor,
        public readonly string $constructionStyle,
        PropertyDefinition ... $propertyDefinitions,
    )
    {
        $this->propertyDefinitions = $propertyDefinitions;
    }
}
