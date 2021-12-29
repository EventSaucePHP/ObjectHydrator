<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithUnmappedStringProperty
{
    public string $name;

    public function __construct(
        string $name
    ) {
        $this->name = $name;
    }
}
