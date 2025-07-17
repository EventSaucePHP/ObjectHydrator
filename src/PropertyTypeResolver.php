<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

interface PropertyTypeResolver
{
    public function typeFromConstructorParameter(
        ReflectionParameter $parameter,
        ReflectionMethod $constructor
    ): PropertyType;

    public function typeFromProperty(
        ReflectionProperty $property,
        ?ReflectionMethod $constructor
    ): PropertyType;

    public function typeFromMethod(ReflectionMethod $method): PropertyType;
}
