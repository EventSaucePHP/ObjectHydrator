<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\TypeSerializers;

use Attribute;
use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\TypeSerializer;
use Ramsey\Uuid\UuidInterface;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class SerializeUuidToString implements TypeSerializer
{
    public function serialize(mixed $value, ObjectSerializer $serializer): mixed
    {
        return $value instanceof UuidInterface
            ? $value->toString()
            : $value;
    }
}
