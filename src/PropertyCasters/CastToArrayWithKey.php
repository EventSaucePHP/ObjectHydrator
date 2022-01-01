<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class CastToArrayWithKey implements PropertyCaster
{
    public function __construct(private string $key)
    {
    }

    public function cast(mixed $value, ObjectHydrator $hydrator): mixed
    {
        return [$this->key => $value];
    }
}
