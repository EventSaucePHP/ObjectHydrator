<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

/**
 * @internal
 */
final class PropertyHydrationDefinition
{
    public function __construct(
        /** @var array<string, array<string>> */
        public array $keys,
        public string $accessorName,
        public array $casters,
        public PropertyType $propertyType,
        public bool $canBeHydrated,
        public bool $isEnum,
        public bool $nullable,
        public bool $hasDefaultValue,
        public ?string $firstTypeName,
        public ?string $typeKey,
        public array $typeMap = [],
    ) {
    }

    public function isBackedEnum(): bool
    {
        return $this->propertyType->isBackedEnum();
    }
}
