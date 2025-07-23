<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty as CamelClass;

class ClassThatCastsListsToBasedOnDocComments
{
    /**
     * @param CamelClass[]              $shortList
     * @param array<string, CamelClass> $map
     * @param array<CamelClass>         $array
     * @param list<CamelClass>          $list
     */
    public function __construct(
        public array $shortList,
        public array $map,
        public array $array,
        public array $list,
    ) {
    }
}
