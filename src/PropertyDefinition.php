<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

/**
 * @internal
 */
final class PropertyDefinition
{
    public function __construct(
        /** @var array<string, array<string>> */
        public array $keys,
        public string $property,
        public array $propertyCasters,
        public bool $canBeHydrated,
        public bool $isEnum,
        public ?string $concreteTypeName,
    ) {
    }
}
