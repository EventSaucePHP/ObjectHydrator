<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

enum CustomEnum: string
{
    case VALUE_ONE = 'one';
    case VALUE_TWO = 'two';
}
