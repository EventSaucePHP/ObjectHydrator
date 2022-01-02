<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\MapFrom;

#[ExampleData(['value' => 'yes', 'age' => 34, 'name' => 'Frank'])]
final class ClassThatUsesClassWithMultipleProperties
{
    public function __construct(
        public string $value,
        #[MapFrom(['age', 'name'])]
        public ClassWithMultipleProperties $child,
    ) {
    }
}
