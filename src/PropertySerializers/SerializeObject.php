<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertySerializers;

use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\PropertySerializer;

class SerializeObject implements PropertySerializer
{
    public function serialize(mixed $value, ObjectSerializer $serializer): mixed
    {
        return $serializer->serializeObject($value);
    }
}
