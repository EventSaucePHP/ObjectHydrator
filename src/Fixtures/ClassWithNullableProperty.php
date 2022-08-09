<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithNullableProperty
{
    public function __construct(public ?string $defaultsToNull)
    {
    }
}
