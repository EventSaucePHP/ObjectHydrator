<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;

use function is_int;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToDateTimeImmutable implements PropertyCaster
{
    public function __construct(private readonly ?string $format)
    {
    }

    public function cast(mixed $value, ObjectHydrator $hydrator): mixed
    {
        if ($this->format !== null) {
            return DateTimeImmutable::createFromFormat($this->format, $value);
        }

        if (is_int($value)) {
            $value = "@$value";
        }

        return new DateTimeImmutable($value);
    }
}
