<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\FixturesFor81;

class ClassWithUnitEnumProperty
{
    public function __construct(public readonly OptionUnitEnum $enum)
    {
    }
}
