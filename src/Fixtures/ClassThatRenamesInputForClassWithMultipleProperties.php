<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\MapFrom;

class ClassThatRenamesInputForClassWithMultipleProperties
{
    public function __construct(

        #[MapFrom(['mapped_age' => 'age', 'name'])]
        public ClassWithMultipleProperties $child,
    ) {
    }
}
