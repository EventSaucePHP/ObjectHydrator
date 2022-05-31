<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ObjectMapperUsingReflectionSerializationTest extends ObjectSerializationTestCase
{
    public function objectHydrator(): ObjectMapper
    {
        return new ObjectMapperUsingReflection();
    }

    protected function objectHydratorFor81(): ObjectMapper
    {
        return $this->objectHydrator();
    }
}
