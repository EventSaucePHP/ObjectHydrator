<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface PropertySerializer
{
    public function serialize(mixed $value, ObjectMapper $hydrator): mixed;
}
