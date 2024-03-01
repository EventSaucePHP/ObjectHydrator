<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithDefaultValueProvidingCaster
{
    public function __construct(
        #[DefaultValueProvidingCaster]
        public string $valueProvidedFromCaster,
    ) {
    }
}
