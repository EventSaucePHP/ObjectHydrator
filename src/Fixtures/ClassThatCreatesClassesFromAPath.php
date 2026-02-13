<?php


declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

final class ClassThatCreatesClassesFromAPath
{
    /**
     * @param array<int, ClassThatCreatesClassesFromAPathSubclass> $subClasses
     */
    public function __construct(
        #[MapFrom('root.list.classes', '.')]
        #[CastListToType(ClassThatCreatesClassesFromAPathSubclass::class)]
        public array $subClasses,
    ) {
    }
}
