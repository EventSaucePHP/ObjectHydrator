<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\Constructor;

class ClassWithStaticConstructor
{
    public string $name;

    private function __construct(string $name) {
        $this->name = $name;
    }

    #[Constructor]
    public static function buildMe(string $name): self
    {
        return new static($name);
    }
}
