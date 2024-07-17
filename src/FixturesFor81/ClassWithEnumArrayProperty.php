<?php

namespace EventSauce\ObjectHydrator\FixturesFor81;

final class ClassWithEnumArrayProperty
{
    public function __construct(
        public array $values,
    ) {
    }
}
