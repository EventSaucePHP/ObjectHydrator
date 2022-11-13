<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures\TypeMapping;

class Dog implements Animal
{
    public function __construct(public string $name)
    {
    }

    public function speak(): string
    {
        return 'woof';
    }
}