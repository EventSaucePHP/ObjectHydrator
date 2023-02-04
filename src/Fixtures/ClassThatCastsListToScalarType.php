<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class ClassThatCastsListToScalarType
{
    /**
     * @param string[] $test
     */
    public function __construct(
        #[CastListToType('string')]
        public array $test,
    ) {
    }
}