<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use DateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyOptions;

class ClassWithFormattedDateTimeInput
{
    public function __construct(
        #[PropertyOptions(['datetime_format' => '!d-m-Y'])]
        public readonly DateTimeImmutable $date
    )
    {
    }
}
