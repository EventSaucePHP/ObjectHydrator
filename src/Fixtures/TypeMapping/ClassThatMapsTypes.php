<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures\TypeMapping;

use EventSauce\ObjectHydrator\MapToType;

class ClassThatMapsTypes
{
    public function __construct(
        #[MapToType('animal', [
            'frog' => Frog::class,
            'dog' => Dog::class,
        ])]
        public Animal $child
    ) {
    }
}