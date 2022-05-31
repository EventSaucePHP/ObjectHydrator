<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ObjectHydratorTest extends ObjectHydratorTestCase
{
    protected function createObjectHydrator(DefinitionProvider $definitionProvider = null): ObjectHydrator
    {
        $definitionProvider ??= new DefinitionProvider(
            keyFormatter: new KeyFormatterWithoutConversion()
        );
        return new ObjectHydratorUsingReflection($definitionProvider);
    }
}
