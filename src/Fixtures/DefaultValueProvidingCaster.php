<?php

namespace EventSauce\ObjectHydrator\Fixtures;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class DefaultValueProvidingCaster implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        return 'some_default_value';
    }
}
