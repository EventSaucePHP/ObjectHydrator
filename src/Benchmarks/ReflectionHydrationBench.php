<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Benchmarks;

use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\ReflectionDefinitionProvider;

class ReflectionHydrationBench extends HydrationBenchCase
{
    protected function createObjectHydrator(): ObjectHydrator
    {
        return new ObjectHydrator();
    }
}
