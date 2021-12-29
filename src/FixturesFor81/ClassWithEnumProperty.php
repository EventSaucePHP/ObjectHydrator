<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\FixturesFor81;

class ClassWithEnumProperty
{
    public function __construct(
        public CustomEnum $enum,
    )
    {
    }
}
