<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Benchmarks;

use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\ObjectMapper;

class ReflectionHydrationBench extends HydrationBenchCase
{
    protected function createObjectMapper(): ObjectMapper
    {
        return new ObjectMapperUsingReflection();
    }
}
