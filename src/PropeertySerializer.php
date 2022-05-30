<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface PropeertySerializer
{
    public function serialize(mixed $value, ObjectSerializer $serializer): mixed;
}
