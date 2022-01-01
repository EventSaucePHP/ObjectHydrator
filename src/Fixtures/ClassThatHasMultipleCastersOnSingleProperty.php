<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\PropertyCasters\CastToArrayWithKey;
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;

final class ClassThatHasMultipleCastersOnSingleProperty
{
    public function __construct(
        #[CastToType('string')]
        #[CastToArrayWithKey('name')]
        public ClassWithStaticConstructor $child,
    ) {
    }
}
