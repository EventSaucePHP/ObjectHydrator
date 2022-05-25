<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class KeyFormattingWithoutConversion implements KeyFormatter
{
    public function propertyNameToKey(string $propertyName): string
    {
        return $propertyName;
    }
}
