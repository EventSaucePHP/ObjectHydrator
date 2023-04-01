<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithInterfaceMapperSettings implements InterfaceWithMapperSettings
{
    public function __construct(
        public string $mappedProperty = 'yes',
    ) {
    }

    public function isMapped(): string
    {
        return 'no';
    }
}
