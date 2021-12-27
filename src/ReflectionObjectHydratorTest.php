<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ReflectionObjectHydratorTest extends ObjectHydratorTestCase
{
    protected function createObjectHydrator(): ReflectionObjectHydrator
    {
        return new ReflectionObjectHydrator();
    }
}
