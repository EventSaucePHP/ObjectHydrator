<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\MapFrom;

class ClassWithMappedStringProperty
{
    public string $name;

    public function __construct(
        #[MapFrom('my_name')]
        string $name
    ) {
        $this->name = $name;
    }
}
