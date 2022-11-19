<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures\CastersOnClasses;

use EventSauce\ObjectHydrator\MapFrom;

#[MapFrom(['first' => 'one', 'second' => 'two'])]
class ClassWithClassLevelMapFromMultiple
{
    public function __construct(
        public int $one,
        public int $two,
    ) {
    }
}