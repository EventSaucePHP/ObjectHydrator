<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface KeyFormatter
{
    public function formatPropertyName(string $propertyName): string;
}
