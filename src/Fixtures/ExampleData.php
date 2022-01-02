<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ExampleData
{
    public function __construct(public array $payload)
    {
    }
}
