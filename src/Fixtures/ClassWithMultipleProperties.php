<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithMultipleProperties
{
    public function __construct(
        public int $age,
        public string $name,
    ) {
    }
}
