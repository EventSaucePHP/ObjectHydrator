<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ObjectSettings
{
    public function __construct(
        public bool $serializePublicMethods = true,
        public bool $serializePublicProperties = true,
    ) {
    }
}