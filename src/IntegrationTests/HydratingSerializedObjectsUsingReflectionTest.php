<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use EventSauce\ObjectHydrator\ObjectHydratorUsingReflection;
use EventSauce\ObjectHydrator\ObjectHydrator;

class HydratingSerializedObjectsUsingReflectionTest extends HydratingSerializedObjectsTestCase
{
    public function objectHydrator(): ObjectHydrator
    {
        return new ObjectHydratorUsingReflection();
    }
}
