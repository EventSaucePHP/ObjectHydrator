<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface KeyFormatter
{
    public function propertyNameToKey(string $propertyName): string;

    public function keyToPropertyName(string $key): string;
}
