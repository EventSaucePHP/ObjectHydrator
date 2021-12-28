<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;

use function settype;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToType implements PropertyCaster
{
    public function __construct(
        private readonly string $type
    )
    {
    }

    public function cast(mixed $value, ObjectHydrator $hydrator): mixed
    {
        settype($value, $this->type);

        return $value;
    }
}
