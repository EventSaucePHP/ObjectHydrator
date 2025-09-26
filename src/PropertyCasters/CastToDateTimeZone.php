<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use DateTimeZone;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;
use function assert;
use function is_string;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class CastToDateTimeZone implements PropertyCaster, PropertySerializer
{
    public function cast(mixed $value, ObjectMapper $hydrator): DateTimeZone
    {
        assert(is_string($value), 'value is expected to be a string');

        return new DateTimeZone($value);
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): string
    {
        assert($value instanceof DateTimeZone, 'value is expected to be a DateTimeZone');

        return $value->getName();
    }
}
