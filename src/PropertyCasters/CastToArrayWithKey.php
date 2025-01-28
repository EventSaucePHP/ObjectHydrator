<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;
use function is_object;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class CastToArrayWithKey implements PropertyCaster, PropertySerializer
{
    public function __construct(private string $key)
    {
    }

    public function cast(mixed $value, ObjectMapper $hydrator, ?string $expectedTypeName): mixed
    {
        return [$this->key => $value];
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        if (is_object($value)) {
            $value = $hydrator->serializeObject($value);
        }

        return $value[$this->key] ?? null;
    }
}
