<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\PropertyCasters\CastToUuid;
use Ramsey\Uuid\UuidInterface;

class ClassWithUuidProperty
{
    public function __construct(
        #[CastToUuid]
        public UuidInterface $id,
    )
    {
    }
}
