<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassReferencedByUnionTwo
{
    public function __construct(public string $text)
    {
    }
}
