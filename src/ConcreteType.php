<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use BackedEnum;
use UnitEnum;

use function function_exists;
use function is_a;

/**
 * @internal
 */
final class ConcreteType
{
    public function __construct(public string $name, public bool $isBuiltIn)
    {
    }

    public function isUnitEnum(): bool
    {
        return function_exists('enum_exists') && is_a($this->name, UnitEnum::class, true)
            && ! $this->isBackedEnum();
    }

    public function isBackedEnum(): bool
    {
        return function_exists('enum_exists') && is_a($this->name, BackedEnum::class, true);
    }
}
