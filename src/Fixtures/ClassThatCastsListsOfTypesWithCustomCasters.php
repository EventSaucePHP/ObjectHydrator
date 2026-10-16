<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use DateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

final readonly class ClassThatCastsListsOfTypesWithCustomCasters
{
    public function __construct(
        #[CastListToType(DateTimeImmutable::class)]
        public array $dates,
    ) {
    }
}
