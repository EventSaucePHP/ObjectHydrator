<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;

use function var_dump;

class CastToClassWithStaticConstructor implements PropertyCaster
{
    public function cast(mixed $value, array $options, ObjectHydrator $hydrator): mixed
    {
        return $hydrator->hydrateObject(ClassWithStaticConstructor::class, ['name' => $value]);
    }
}
