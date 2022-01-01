<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

final class ClassWithUnmappedStringProperty
{
    public function __construct(
        public string $name,
    ) {
    }
}
