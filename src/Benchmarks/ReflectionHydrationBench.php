<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Benchmarks;

use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;

class ReflectionHydrationBench extends HydrationBenchCase
{
    protected function createObjectMapper(): ObjectMapper
    {
        return new ObjectMapperUsingReflection();
    }
}
