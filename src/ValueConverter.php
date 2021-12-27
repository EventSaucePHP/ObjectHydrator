<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface ValueConverter
{
    public function canConvert(string $typeName): bool;

    public function convert(string $typeName, mixed $value, array $options): mixed;
}
