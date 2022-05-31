<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ObjectHydratorSerializerTest extends ObjectSerializerTestCase
{
    public function objectHydrator(): ObjectHydrator
    {
        return new ObjectHydratorUsingReflection();
    }

    protected function objectHydratorFor81(): ObjectHydrator
    {
        return $this->objectHydrator();
    }
}
