<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\MapFrom;

class ClassThatUsesClassWithMultipleProperties
{
    public function __construct(

        public string $value,
        #[MapFrom(['age', 'name'])]
        public ClassWithMultipleProperties $child,
    ) {
    }
}
