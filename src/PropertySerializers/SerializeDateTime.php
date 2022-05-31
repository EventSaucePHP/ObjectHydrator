<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertySerializers;

use Attribute;
use DateTimeInterface;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertySerializer;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class SerializeDateTime implements PropertySerializer
{
    public function __construct(private string $format = 'Y-m-d H:i:s.uO')
    {
    }

    public function serialize(mixed $value, ObjectHydrator $hydrator): mixed
    {
        return $value instanceof DateTimeInterface
            ? $value->format($this->format)
            : $value;
    }
}
