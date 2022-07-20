<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\FixturesFor81;

class ClassWithIntegerEnumProperty
{
    public function __construct(public readonly IntegerEnum $enum)
    {
    }
}
