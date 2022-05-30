<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertySerializers;

use Attribute;
use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\PropeertySerializer;
use Ramsey\Uuid\UuidInterface;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class SerializeUuidToString implements PropeertySerializer
{
    public function serialize(mixed $value, ObjectSerializer $serializer): mixed
    {
        return $value instanceof UuidInterface
            ? $value->toString()
            : $value;
    }
}
