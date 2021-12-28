<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;
use LogicException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToUuid implements PropertyCaster
{
    public function __construct(private readonly string $type = 'string')
    {
    }

    public function cast(mixed $value, ObjectHydrator $hydrator): UuidInterface
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
}
