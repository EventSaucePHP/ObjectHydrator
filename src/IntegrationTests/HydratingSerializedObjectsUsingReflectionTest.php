<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\ObjectMapper;

class HydratingSerializedObjectsUsingReflectionTest extends HydratingSerializedObjectsTestCase
{
    public function objectHydrator(): ObjectMapper
    {
        return new ObjectMapperUsingReflection();
    }
}
