<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use DateTimeImmutable;

final class ClassWithNullableInput
{
    public function __construct(
        public ?DateTimeImmutable $date = null
    ) {
    }
}
