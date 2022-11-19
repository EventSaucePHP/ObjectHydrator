<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures\CastersOnClasses;

use EventSauce\ObjectHydrator\MapFrom;

#[MapFrom('nested')]
class ClassWithClassLevelMapFrom
{
    public function __construct(public string $name)
    {
    }
}