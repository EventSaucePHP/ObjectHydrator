<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use function in_array;

/**
 * @internal
 */
final class ClassHydrationDefinition
{
    /** @var PropertyHydrationDefinition[] */
    public array $propertyDefinitions;

    public function __construct(
        public string $constructor,
        public string $constructionStyle,
        public ?string $typeKey,
        public array $typeMap,
        public false|array $mapFrom,
        PropertyHydrationDefinition ...$propertyDefinitions,
    ) {
        $this->propertyDefinitions = $propertyDefinitions;
    }

    public function canBeConstructed(): bool
    {
        return in_array($this->constructionStyle, ['new', 'static']);
    }
}
