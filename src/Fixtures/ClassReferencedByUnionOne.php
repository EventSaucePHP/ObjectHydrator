<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassReferencedByUnionOne
{
    public function __construct(public int $number)
    {
    }
}
