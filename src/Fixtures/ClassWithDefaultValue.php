<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithDefaultValue
{
    public function __construct(public string $requiredValue, public string $defaultValue = 'default')
    {
    }
}
