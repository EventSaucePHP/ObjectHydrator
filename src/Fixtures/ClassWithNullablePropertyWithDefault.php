<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithNullablePropertyWithDefault
{
    public function __construct(public ?string $defaultUsed = 'default_used')
    {
    }
}
