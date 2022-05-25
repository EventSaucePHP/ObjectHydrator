<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use DateTimeImmutable;
use DateTimeInterface;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyCasters\CastToUuid;
use Ramsey\Uuid\UuidInterface;

final class DefaultCasterRepository
{
    /** @var array<string, array<string, array>> */
    private array $casters = [];

    /**
     * BC forwarding function
     */
    public static function buildIn(): static
    {
        return static::builtIn();
    }

    public static function builtIn(): static
    {
        $repository = new static();
        $repository->registerDefaultCaster(DateTimeImmutable::class, CastToDateTimeImmutable::class);
        $repository->registerDefaultCaster(DateTimeInterface::class, CastToDateTimeImmutable::class);
        $repository->registerDefaultCaster(UuidInterface::class, CastToUuid::class);

        return $repository;
    }

    /**
     * @param class-string                 $propertyClassName
     * @param class-string<PropertyCaster> $casterClassName
     *
     * @return $this
     */
    public function registerDefaultCaster(
        string $propertyClassName,
        string $casterClassName,
        array $arguments = []
    ): static {
        $this->casters[$propertyClassName] = [$casterClassName, $arguments];

        return $this;
    }

    public function casterFor(string $propertyClassName): ?array
    {
        return $this->casters[$propertyClassName] ?? null;
    }
}
