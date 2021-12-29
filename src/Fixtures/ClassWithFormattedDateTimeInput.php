<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use DateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;

class ClassWithFormattedDateTimeInput
{
    public DateTimeImmutable $date;

    public function __construct(
        #[CastToDateTimeImmutable('!d-m-Y')]
        DateTimeImmutable $date
    )
    {
        $this->date = $date;
    }
}
