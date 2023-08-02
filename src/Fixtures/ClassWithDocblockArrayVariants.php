<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithDocblockArrayVariants
{
    /**
     * Constructor.
     *
     * @param array[] $test
     *   Param name.
     * @param string[] $test2
     *   Param 2 name.
     * @param array<string> $test3
     *   Param 3 name.
     * @param array<string, int> $test4
     *   Param 4 name.
     * @param array $test5
     *   Param 5 name.
     * @param \EventSauce\ObjectHydrator\Fixtures\ClassWithNullableProperty[] $test6
     *   Param 6 name.
     * @param EventSauce\ObjectHydrator\Fixtures\ClassWithNullableProperty[] $test7
     *   Param 7 name.
     * @param ClassWithNullableProperty[] $test8
     *   Param 8 name.
     * @param array<\EventSauce\ObjectHydrator\Fixtures\ClassWithNullableProperty> $test9
     *   Param 9 name.
     * @param array<int, \EventSauce\ObjectHydrator\Fixtures\ClassWithNullableProperty> $test10
     *   Param 10 name.
     */
    public function __construct(
        public array $test,
        public array $test2,
        public array $test3,
        public array $test4,
        public array $test5,
        public array $test6,
        public array $test7,
        public array $test8,
        public array $test9,
        public array $test10,
    ) {
    }
}
