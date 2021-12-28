<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\MapFrom;

class ClassWithMappedStringProperty
{
    public function __construct(
        #[MapFrom('my_name')]
        public string $name,
    ) {
    }
}
