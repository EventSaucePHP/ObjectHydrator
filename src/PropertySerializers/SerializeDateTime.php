<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertySerializers;

use Attribute;
use DateTimeInterface;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertySerializer;
use function assert;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class SerializeDateTime implements PropertySerializer
{
    public function __construct(private string $format = 'Y-m-d H:i:s.uO')
    {
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert($value instanceof DateTimeInterface);

        return $value->format($this->format);
    }
}
