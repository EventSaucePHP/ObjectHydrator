<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastUsing
{
    /**
     * @param class-string<PropertyCaster> $className
     */
    public function __construct(public string $className, array $options = [])
    {
    }
}
