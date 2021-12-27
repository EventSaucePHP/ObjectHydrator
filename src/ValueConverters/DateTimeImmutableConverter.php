<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\ValueConverters;

use DateTimeImmutable;
use EventSauce\ObjectHydrator\ValueConverter;

use function is_int;

class DateTimeImmutableConverter implements ValueConverter
{
    public function canConvert(string $typeName): bool
    {
        return DateTimeImmutable::class === $typeName;
    }

    public function convert(string $typeName, mixed $value, array $options): mixed
    {
        $dateTimeFormat = $options['datetime_format'] ?? null;

        if ($dateTimeFormat !== null) {
            return DateTimeImmutable::createFromFormat($dateTimeFormat, $value);
        }

        if (is_int($value)) {
            $value = "@$value";
        }

        return new DateTimeImmutable($value);
    }
}
