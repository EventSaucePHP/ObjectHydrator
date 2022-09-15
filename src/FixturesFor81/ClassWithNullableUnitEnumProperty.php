<?php

namespace EventSauce\ObjectHydrator\FixturesFor81;

final class ClassWithNullableUnitEnumProperty
{
    public function __construct(
        public readonly ?OptionUnitEnum $enum,
    ) {
    }
}
