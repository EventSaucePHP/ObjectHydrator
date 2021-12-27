<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassThatContainsAnotherClass
{
    public function __construct(
        public readonly ClassWithUnmappedStringProperty $child
    )
    {
    }
}
