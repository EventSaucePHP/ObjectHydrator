<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;

use function settype;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastListToType implements PropertyCaster
{
    public function __construct(
        private string $type
    ) {
    }

    public function cast(mixed $value, ObjectHydrator $hydrator): mixed
    {
        foreach ((array) $value as &$item) {
            settype($item, $this->type);
        }

        return $value;
    }
}
