<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertySerializers;

use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertySerializer;

class SerializeObject implements PropertySerializer
{
    public function serialize(mixed $value, ObjectHydrator $hydrator): mixed
    {
        return $hydrator->serializeObject($value);
    }
}
