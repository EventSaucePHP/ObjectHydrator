<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

/**
 * @internal
 */
class PropertyDefinition
{
    public function __construct(
        public string $key,
        public string $property,
        public array $propertyCasters,
//        public ?string $propertyCaster,
//        public array $castingOptions,
        public bool $canBeHydrated,
        public bool $isEnum,
        public ?string $concreteTypeName,
    )
    {
    }
}
