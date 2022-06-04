<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty as CamelClass;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class ClassThatCastsListsToDifferentTypes
{
    /**
     * @param CamelClass[] $first
     */
    public function __construct(
        #[CastListToType(CamelClass::class)]
        public array $first,

        #[CastListToType(ClassWithPropertyCasting::class)]
        public array $second,
    ) {
    }
}
