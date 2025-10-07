<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\FixturesFor81;

final class ClassWithEnumArrayProperty
{
    public function __construct(
        public array $values,
    ) {
    }
}
