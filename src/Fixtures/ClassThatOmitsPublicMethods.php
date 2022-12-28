<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\ObjectSettings;

#[ObjectSettings(serializePublicMethods: false)]
class ClassThatOmitsPublicMethods
{
    public function __construct(
        public string $included = "included!"
    ) {
    }

    public function excluded(): string
    {
        return "excluded!";
    }
}