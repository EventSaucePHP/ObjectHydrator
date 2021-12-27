<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\ValueConverters;

use EventSauce\ObjectHydrator\ValueConverter;
use LogicException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidConverter implements ValueConverter
{
    public function canConvert(string $typeName): bool
    {
        return UuidInterface::class === $typeName || Uuid::class === $typeName;
    }

    public function convert(string $typeName, mixed $value, array $options): mixed
    {
        $type = (string) ($options['uuid_type'] ?? 'string');
        $value = (string) $value;

        return match ($type) {
            'string' => Uuid::fromString($value),
            'bytes' => Uuid::fromBytes($value),
            'int' => Uuid::fromInteger($value),
            'integer' => Uuid::fromInteger($value),
            default => throw new LogicException('Unexpected UUID type: ' . $type),
        };
    }
}
