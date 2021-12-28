<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class PropertyDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $property,
        public readonly ?string $propertyCaster,
        public readonly array $castingOptions,
        public readonly bool $canBeHydrated,
        public readonly bool $isEnum,
        public readonly ?string $concreteTypeName,
    )
    {
    }
}
