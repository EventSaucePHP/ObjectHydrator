<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;

class HydratingSerializedObjectsUsingReflectionTest extends HydratingSerializedObjectsTestCase
{
    public function objectHydrator(): ObjectMapper
    {
        return new ObjectMapperUsingReflection();
    }
}
