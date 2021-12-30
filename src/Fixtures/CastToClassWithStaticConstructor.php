<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use Attribute;
use EventSauce\ObjectHydrator\PropertyCaster;

use EventSauce\ObjectHydrator\ObjectHydrator;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToClassWithStaticConstructor implements PropertyCaster
{
    public function cast($value, ObjectHydrator $hydrator)
    {
        return $hydrator->hydrateObject(ClassWithStaticConstructor::class, ['name' => $value]);
    }
}
