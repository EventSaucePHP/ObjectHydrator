<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\CastUsing;

class ClassWithComplexTypeThatIsMapped
{
    public function __construct(
        #[CastUsing(CastToClassWithStaticConstructor::class)]
        public readonly ClassWithStaticConstructor|ClassWithUnmappedStringProperty $child
    )
    {
    }
}
