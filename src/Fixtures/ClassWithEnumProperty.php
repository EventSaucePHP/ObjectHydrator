<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithEnumProperty
{
    public function __construct(
        public CustomEnum $enum,
    )
    {
    }
}
