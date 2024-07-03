<?php

namespace EventSauce\ObjectHydrator\Fixtures;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class CastEmptyStringToNull implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator, ?string $expectedTypeName): mixed
    {
        if ($value === '') {
            return null;
        }

        return $value;
    }
}
