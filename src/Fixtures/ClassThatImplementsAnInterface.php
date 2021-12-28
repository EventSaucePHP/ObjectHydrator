<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassThatImplementsAnInterface implements InterfaceToFilterOn
{
    public function __construct(
        public string $name
    )
    {
    }
}
