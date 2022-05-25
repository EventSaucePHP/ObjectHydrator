<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface ObjectSerializer
{
    public function serializeObject(object $object): array;
}
