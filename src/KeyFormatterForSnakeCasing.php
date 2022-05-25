<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use function strtolower;

class KeyFormatterForSnakeCasing implements KeyFormatter
{
    public function formatPropertyName(string $propertyName): string
    {
        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $propertyName));
    }
}
