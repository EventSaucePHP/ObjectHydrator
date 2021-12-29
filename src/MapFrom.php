<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapFrom
{
    public function __construct(public string $name)
    {
    }
}
