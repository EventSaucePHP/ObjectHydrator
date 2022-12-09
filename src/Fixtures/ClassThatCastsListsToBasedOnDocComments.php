<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty as CamelClass;

class ClassThatCastsListsToBasedOnDocComments
{
    /**
     * @param int                       $number
     * @param CamelClass[]              $list
     * @param array<string, CamelClass> $map
     * @param array<CamelClass>         $map
     */
    public function __construct(
        public int $number,
        public array $list,
        public array $map,
        public array $array,
    ) {
    }
}
