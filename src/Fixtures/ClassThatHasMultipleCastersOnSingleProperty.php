<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\PropertyCasters\CastToArrayWithKey;
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;

#[ExampleData(['child' => 12345])]
final class ClassThatHasMultipleCastersOnSingleProperty
{
    public function __construct(
        #[CastToType('string', 'int')]
        #[CastToArrayWithKey('name')]
        public ClassWithStaticConstructor $child,
    ) {
    }
}
