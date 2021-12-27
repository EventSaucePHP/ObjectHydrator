<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface PropertyMapper
{
    public function mapProperty(mixed $property, ObjectHydrator $hydrator): mixed;
}
