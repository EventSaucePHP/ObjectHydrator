<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithUnionProperty
{
    public function __construct(public ClassReferencedByUnionOne|ClassReferencedByUnionTwo $union)
    {
    }
}
