<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

final class ClassWithCamelCaseProperty
{
    public function __construct(public string $snakeCase)
    {
    }
}
