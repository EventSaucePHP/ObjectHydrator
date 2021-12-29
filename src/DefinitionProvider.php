<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Throwable;

/**
 * @internal
 */
interface DefinitionProvider
{
    /**
     * @throws Throwable
     */
    public function provideDefinition(string $className): ClassDefinition;
}
