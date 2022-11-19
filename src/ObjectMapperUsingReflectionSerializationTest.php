<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ObjectMapperUsingReflectionSerializationTest extends ObjectSerializationTestCase
{
    public function objectMapper(): ObjectMapper
    {
        return new ObjectMapperUsingReflection();
    }

    protected function objectMapperFor81(): ObjectMapper
    {
        return $this->objectMapper();
    }
}
