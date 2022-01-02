<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\Constructor;

final class ClassWithStaticConstructor
{
    private function __construct(public string $name)
    {
    }

    #[Constructor]
    public static function buildMe(string $name): static
    {
        return new static($name);
    }
}
