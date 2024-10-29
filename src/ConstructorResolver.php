<?php

namespace EventSauce\ObjectHydrator;

use ReflectionClass;
use ReflectionMethod;

interface ConstructorResolver
{
    public function resolveConstructor(ReflectionClass $reflectionClass): ?ReflectionMethod;
}
