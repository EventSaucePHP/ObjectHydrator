<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithDocblockAndArrayFollowingScalar
{
    /**
     * Constructor.
     *
     * @param string $test
     *   Param name.
     * @param string[] $test2
     *   Param 2 name.
     */
    public function __construct(
        public readonly string $test,
        protected array $test2,
    ) {
    }
}
