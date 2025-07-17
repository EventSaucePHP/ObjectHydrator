<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty as CamelClass;

final class ClassThatSpecifiesArraysWithDocComments
{
    /**
     * @param array<string, CamelClass> $mapWithObjects
     * @param array<string, int> $mapWithScalars
     * @param array<string, array<string, string>> $mapWithAssociativeArrays
     * @param array<int, string> $listWithTypeHint
     */
    public function __construct(
        public array $mapWithObjects,
        public array $mapWithScalars,
        public array $mapWithAssociativeArrays,
        public array $listWithoutTypeHint,
        public array $listWithTypeHint,
    ) {
    }

    /**
     * @return array<string, CamelClass>
     */
    public function methodMapWithObjects(): array
    {
        return $this->mapWithObjects;
    }

    /**
     * @return array<string, int>
     */
    public function methodMapWithScalars(): array
    {
        return $this->mapWithScalars;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function methodMapWithAssociativeArrays(): array
    {
        return $this->mapWithAssociativeArrays;
    }

    public function methodListWithoutTypeHint(): array
    {
        return $this->listWithoutTypeHint;
    }

    /**
     * @return array<int, string>
     */
    public function methodListWithTypeHint(): array
    {
        return $this->listWithTypeHint;
    }
}