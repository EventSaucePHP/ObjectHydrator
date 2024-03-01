<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;
use function assert;
use function is_int;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class CastToDateTimeImmutable implements PropertyCaster, PropertySerializer
{
    public function __construct(private ?string $format = null, private ?string $timeZone = null)
    {
    }

    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        if($value === null){
            return null;
        }

        $timeZone = $this->timeZone ? new DateTimeZone($this->timeZone) : $this->timeZone;

        if ($this->format !== null) {
            return DateTimeImmutable::createFromFormat($this->format, $value, $timeZone);
        }

        if (is_int($value)) {
            $value = "@$value";
        }

        return new DateTimeImmutable($value, $timeZone);
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert($value instanceof DateTimeInterface);

        return $value->format($this->format ?: 'Y-m-d H:i:s.uO');
    }
}
