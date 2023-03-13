<?php

declare(strict_types=1);


namespace EventSauce\ObjectHydrator;

class SkippableTypesRepository
{
    public function __construct(
        private array $types
    ) { }

    public static function createDefault(): SkippableTypesRepository
    {
        return new self([Skippable::class => true]);
    }

    /**
     * @param class-string $classname
     */
    public function addType(string $classname): static
    {
        $this->types[$classname] = true;

        return $this;
    }

    public function removeType($classname): static
    {
        if (isset($this->types[$classname])) {
            unset($this->types[$classname]);
        }

        return $this;
    }

    /**
     * @param class-string $classname
     */
    public function hasType(string $classname): bool
    {
        return isset($this->types[$classname]);
    }
}