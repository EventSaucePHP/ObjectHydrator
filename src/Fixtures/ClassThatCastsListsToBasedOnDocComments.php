<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty as CamelClass;

class ClassThatCastsListsToBasedOnDocComments
{
    /**
     * @param CamelClass[] $list
     */
    public function __construct(
        public array $list
    ) {
    }
}
