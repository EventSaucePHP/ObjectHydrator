<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\ObjectSettings;

#[ObjectSettings(serializePublicProperties: false)]
class ClassThatOmitsPublicProperties
{
    public function __construct(
        public string $excluded = "excluded!"
    ) {
    }

    public function included(): string
    {
        return "included!";
    }
}