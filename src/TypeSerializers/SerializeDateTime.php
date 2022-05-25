<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\TypeSerializers;

use Attribute;
use DateTimeInterface;
use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\TypeSerializer;


#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class SerializeDateTime implements TypeSerializer
{
    public function __construct(private string $format = 'Y-m-d H:i:s.uO')
    {
    }

    public function serialize(mixed $value, ObjectSerializer $serializer): mixed
    {
        return $value instanceof DateTimeInterface
            ? $value->format($this->format)
            : $value;
    }
}
