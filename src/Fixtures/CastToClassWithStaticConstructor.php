<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use Attribute;
use EventSauce\ObjectHydrator\ObjectHydratorUsingReflection;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class CastToClassWithStaticConstructor implements PropertyCaster
{
    public function cast(mixed $value, ObjectHydrator $hydrator): mixed
    {
        return $hydrator->hydrateObject(ClassWithStaticConstructor::class, ['name' => $value]);
    }
}
