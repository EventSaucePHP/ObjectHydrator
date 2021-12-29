<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\FixturesFor80;

use EventSauce\ObjectHydrator\Fixtures\CastToClassWithStaticConstructor;
use EventSauce\ObjectHydrator\Fixtures\ClassWithStaticConstructor;
use EventSauce\ObjectHydrator\Fixtures\ClassWithUnmappedStringProperty;

class ClassWithComplexTypeThatIsMapped
{
    public $child;

    public function __construct(
        #[CastToClassWithStaticConstructor]
        ClassWithStaticConstructor|ClassWithUnmappedStringProperty $child
    )
    {
        $this->child = $child;
    }
}
