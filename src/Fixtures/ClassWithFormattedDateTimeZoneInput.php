<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use DateTimeZone;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeZone;

#[ExampleData(['timezone' => 'Europe/Amsterdam'])]
final class ClassWithFormattedDateTimeZoneInput
{
    public function __construct(
        #[CastToDateTimeZone]
        public DateTimeZone $timezone,
    ) {
    }
}
