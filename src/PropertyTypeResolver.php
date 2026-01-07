<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

interface PropertyTypeResolver
{
    public function typeFromConstructorParameter(
        ReflectionParameter|ReflectionProperty $parameter,
        ReflectionMethod $constructor
    ): PropertyType;
}
