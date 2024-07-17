<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertySerializers;

use Attribute;
use BackedEnum;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertySerializer;
use UnitEnum;
use function assert;
use function is_array;
use function is_object;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class SerializeArrayItems implements PropertySerializer
{
    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert(is_array($value));

        foreach ($value as $index => $item) {
            if (is_object($item)) {
                $value[$index] = match (true) {
                    $item instanceof BackedEnum => $item->value,
                    $item instanceof UnitEnum => $item->name,
                    default => $hydrator->serializeObject($item),
                };
            }
        }

        return $value;
    }
}
