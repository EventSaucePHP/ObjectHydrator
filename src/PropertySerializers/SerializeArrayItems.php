<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertySerializers;

use Attribute;
use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\PropertySerializer;

use function is_array;
use function is_object;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class SerializeArrayItems implements PropertySerializer
{
    public function serialize(mixed $value, ObjectSerializer $serializer): mixed
    {
        if ( ! is_array($value)) {
            return $value;
        }

        foreach ($value as $index => $item) {
            if (is_object($item)) {
                $value[$index] = $serializer->serializeObject($item);
            }
        }

        return $value;
    }
}
