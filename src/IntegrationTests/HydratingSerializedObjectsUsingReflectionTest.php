<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\ObjectSerializerUsingReflection;

class HydratingSerializedObjectsUsingReflectionTest extends HydratingSerializedObjectsTestCase
{
    public function objectSerializer(): ObjectSerializer
    {
        return new ObjectSerializerUsingReflection();
    }

    public function objectHydrator(): ObjectHydrator
    {
        return new ObjectHydrator();
    }
}
