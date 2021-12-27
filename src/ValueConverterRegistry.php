<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use DateTimeImmutable;

use EventSauce\ObjectHydrator\ValueConverters\DateTimeImmutableConverter;

use EventSauce\ObjectHydrator\ValueConverters\UuidConverter;
use Ramsey\Uuid\UuidInterface;

use function array_key_exists;

class ValueConverterRegistry implements ValueConverter
{
    private array $converters = [];
    private array $instances = [];

    /**
     * @param class-string<ValueConverter> $converter
     */
    public function registerConverter(string $type, string $converter): void
    {
        $this->converters[$type] = $converter;
    }

    public function canConvert(string $typeName): bool
    {
        return array_key_exists($typeName, $this->converters);
    }

    public function convert(string $typeName, mixed $value, array $options): mixed
    {
        $converter = $this->converters[$typeName] ?? null;

        if ($converter === null) {
            throw new UnableToConvertProperty("No converter registered for type: $typeName");
        }

        /** @var ValueConverter $instance */
        $instance = $this->instances[$converter] ??= new $converter;

        return $instance->convert($typeName, $value, $options);
    }

    public static function default(): static
    {
        $registry = new static();
        $registry->registerConverter(DateTimeImmutable::class, DateTimeImmutableConverter::class);
        $registry->registerConverter(UuidInterface::class, UuidConverter::class);

        return $registry;
    }
}
