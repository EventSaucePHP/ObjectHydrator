<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\MapFrom;

#[ExampleData(['nested' => ['name' => 'Frank']])]
class ClassWithPropertyMappedFromNestedKey
{
    public function __construct(
        #[MapFrom('nested.name', separator: '.')]
        public string $name
    )
    {
    }
}