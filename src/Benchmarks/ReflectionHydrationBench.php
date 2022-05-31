<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Benchmarks;

use EventSauce\ObjectHydrator\ObjectHydratorUsingReflection;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\ObjectHydrator;

class ReflectionHydrationBench extends HydrationBenchCase
{
    protected function createObjectHydrator(): ObjectHydrator
    {
        return new ObjectHydratorUsingReflection();
    }
}
