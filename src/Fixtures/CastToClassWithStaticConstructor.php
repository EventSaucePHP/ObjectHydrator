<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class CastToClassWithStaticConstructor implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator, ?string $expectedTypeName): mixed
    {
        return $hydrator->hydrateObject(ClassWithStaticConstructor::class, ['name' => $value]);
    }
}
