<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\PropertyCasters\CastToArrayWithKey;
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;

final class ClassThatHasMultipleCastersOnMapProperty
{
    /**
     * @param array<string, array<string, string>> $map
     */
    public function __construct(
        #[CastToType('array')]
        #[CastToArrayWithKey('second_level')]
        #[CastToArrayWithKey('first_level')]
        public array $map,
    ) {
    }
}