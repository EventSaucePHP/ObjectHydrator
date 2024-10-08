<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

#[ExampleData(['children' => [['name' => 'Frank'], ['name' => 'Renske'], ['name' => 'Rover']]])]
final class ClassWithPropertyThatUsesListCastingToClasses
{
    public function __construct(
        #[CastListToType(ClassWithUnmappedStringProperty::class)]
        public array $children,
    ) {
    }

    public function closure(): bool
    {
        $localVariable = false;

        $localFunction = function () use (&$localVariable) {
            $localVariable = true;
        };

        $localFunction();

        return $localVariable;
    }
}
