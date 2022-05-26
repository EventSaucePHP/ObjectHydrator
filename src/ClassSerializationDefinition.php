<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ClassSerializationDefinition
{
    public function __construct(
        /**
         * @var PropertySerializationDefinition[]
         */
        public array $properties
    ) {
    }
}
