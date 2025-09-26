<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

final class ClassThatSpecifiesArrayWithIntegerKeys
{
    /**
     * @param array<int, string> $arrayWithIntegerKeys
     */
    public function __construct(
        public array $arrayWithIntegerKeys,
    ) {
    }
}