<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;

use function is_object;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class CastToArrayWithKey implements PropertyCaster, PropertySerializer
{
    public function __construct(private string $key)
    {
    }

    public function cast(mixed $value, ObjectHydrator $hydrator): mixed
    {
        return [$this->key => $value];
    }

    public function serialize(mixed $value, ObjectSerializer $serializer): mixed
    {
        if (is_object($value)) {
            $value = $serializer->serializeObject($value);
        }

        return $value[$this->key] ?? null;
    }
}
