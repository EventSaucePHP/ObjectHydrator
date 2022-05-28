<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use BackedEnum;

use function is_a;

/**
 * @internal
 */
final class PropertyHydrationDefinition
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

    public function isBackedEnum(): bool
    {
        return is_a((string) $this->concreteTypeName, BackedEnum::class, true);
    }
}
