<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

/**
 * @internal
 */
class ConcreteType
{
    public function __construct(public string $name, public bool $isBuiltIn)
    {
    }
}
