<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\FixturesFor81;

use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class ClassWithEnumListProperty
{
    public function __construct(
        #[CastListToType(CustomEnum::class)]
        public readonly array $enums,
    ) {
    }
}
