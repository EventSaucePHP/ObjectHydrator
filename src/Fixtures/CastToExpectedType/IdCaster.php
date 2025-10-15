<?php

namespace EventSauce\ObjectHydrator\Fixtures\CastToExpectedType;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class IdCaster implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator, ?string $expectedTypeName): mixed
    {
        if ($expectedTypeName === null) {
            throw new \RuntimeException('Expected type name is required');
        }
        return $expectedTypeName::fromString($value);
    }
}
