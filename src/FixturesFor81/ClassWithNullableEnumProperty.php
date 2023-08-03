<?php

namespace EventSauce\ObjectHydrator\FixturesFor81;

use EventSauce\ObjectHydrator\Fixtures\CastEmptyStringToNull;

final class ClassWithNullableEnumProperty
{
    public function __construct(
        public readonly ?CustomEnum $enum,

        #[CastEmptyStringToNull]
        public readonly ?CustomEnum $enumFromEmptyString,
    ) {
    }
}
