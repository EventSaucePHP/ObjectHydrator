<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface ObjectSerializer
{
    /**
     * @throws UnableToSerializeObject
     */
    public function serializeObject(object $object): mixed;
}
