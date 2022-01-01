<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

final class ClassWithComplexTypeThatIsMapped
{
    public function __construct(
        #[CastToClassWithStaticConstructor]
        public ClassWithStaticConstructor|ClassWithUnmappedStringProperty $child
    )
    {
    }
}
