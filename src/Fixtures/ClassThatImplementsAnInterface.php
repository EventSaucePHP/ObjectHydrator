<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassThatImplementsAnInterface implements InterfaceToFilterOn
{
    public string $name;

    public function __construct(
        string $name
    )
    {
        $this->name = $name;
    }
}
