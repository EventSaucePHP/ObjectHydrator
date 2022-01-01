<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use DateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;

final class ClassWithFormattedDateTimeInput
{
    public function __construct(
        #[CastToDateTimeImmutable('!d-m-Y')]
        public DateTimeImmutable $date
    )
    {
    }
}
