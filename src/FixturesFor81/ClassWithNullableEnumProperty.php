<?php

namespace EventSauce\ObjectHydrator\FixturesFor81;

final class ClassWithNullableEnumProperty
{
    public function __construct(
        public readonly ?CustomEnum $enum,
    ) {
    }
}
