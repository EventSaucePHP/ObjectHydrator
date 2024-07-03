<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;
use LogicException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function assert;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class CastToUuid implements PropertyCaster, PropertySerializer
{
    public function __construct(private string $type = 'string')
    {
    }

    public function cast(mixed $value, ObjectMapper $hydrator, ?string $expectedTypeName): UuidInterface
    {
        $value = (string) $value;

        return match ($this->type) {
            'string' => Uuid::fromString($value),
            'bytes' => Uuid::fromBytes($value),
            'int' => Uuid::fromInteger($value),
            'integer' => Uuid::fromInteger($value),
            default => throw new LogicException('Unexpected UUID type: ' . $this->type),
        };
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): string
    {
        assert($value instanceof UuidInterface);

        return match ($this->type) {
            'string' => $value->toString(),
            'bytes' => $value->getBytes(),
            'int' => $value->getInteger()->toString(),
            'integer' => $value->getInteger()->toString(),
            default => throw new LogicException('Unexpected UUID type: ' . $this->type),
        };
    }
}
