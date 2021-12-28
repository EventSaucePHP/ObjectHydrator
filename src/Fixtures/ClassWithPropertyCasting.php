<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\PropertyCasters\CastToType;
use EventSauce\ObjectHydrator\ScalarCasting;

class ClassWithPropertyCasting
{
    public function __construct(
        #[CastToType('int')]
        public int $age,
    ) {
    }
}
