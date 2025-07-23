<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ObjectMapperUsingReflectionSerializationTest extends ObjectSerializationTestCase
{
    public function objectMapper(bool $omitNullValuesOnSerialization = false): ObjectMapper
    {
        return new ObjectMapperUsingReflection(omitNullValuesOnSerialization: $omitNullValuesOnSerialization);
    }

    protected function objectMapperFor81(): ObjectMapper
    {
        return $this->objectMapper();
    }
}
