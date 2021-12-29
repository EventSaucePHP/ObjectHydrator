<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassThatContainsAnotherClass
{
    public ClassWithUnmappedStringProperty $child;

    public function __construct(
        ClassWithUnmappedStringProperty $child
    )
    {
        $this->child = $child;
    }
}
