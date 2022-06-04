<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use EventSauce\ObjectHydrator\PropertySerializers\SerializeArrayItems;
use EventSauce\ObjectHydrator\PropertySerializers\SerializeDateTime;
use EventSauce\ObjectHydrator\PropertySerializers\SerializeUuidToString;
use Ramsey\Uuid\UuidInterface;

class DefaultSerializerRepository
{
    /**
     * @param array<string, array{0: class-string<PropertySerializer> $serializersPerType
     */
    public function __construct(private array $serializersPerType)
    {
    }

    public static function builtIn(): DefaultSerializerRepository
    {
        return new DefaultSerializerRepository([
            'array' => [SerializeArrayItems::class, []],
            UuidInterface::class => [SerializeUuidToString::class, []],
            DateTime::class => [SerializeDateTime::class, []],
            DateTimeImmutable::class => [SerializeDateTime::class, []],
            DateTimeInterface::class => [SerializeDateTime::class, []],
        ]);
    }

    /**
     * @param class-string<PropertySerializer> $serializerClass
     */
    public function registerDefaultSerializer(string $type, string $serializerClass, array $arguments = []): void
    {
        $this->serializersPerType[$type] = [$serializerClass, $arguments];
    }

    public function serializerForType(string $type): ?array
    {
        return $this->serializersPerType[$type] ?? null;
    }

    public function allSerializersPerType(): array
    {
        return $this->serializersPerType;
    }
}
