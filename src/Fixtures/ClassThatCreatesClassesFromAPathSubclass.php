<?php

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassThatCreatesClassesFromAPathSubclass
{
    public function __construct(
        public string $subClassName,
    ) {
    }
}
