<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class ClassThatCastsListsToDifferentTypes
{
    public function __construct(
        #[CastListToType(ClassWithCamelCaseProperty::class)]
        public array $first,

        #[CastListToType(ClassWithPropertyCasting::class)]
        public array $second,
    ) {
    }
}
