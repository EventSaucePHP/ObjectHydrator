<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ObjectMapperUsingReflectionHydrationTest extends ObjectHydrationTestCase
{
    protected function createObjectHydrator(DefinitionProvider $definitionProvider = null): ObjectMapper
    {
        $definitionProvider ??= new DefinitionProvider(
            keyFormatter: new KeyFormatterWithoutConversion()
        );
        return new ObjectMapperUsingReflection($definitionProvider);
    }
}
