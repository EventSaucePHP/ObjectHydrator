<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures\TypeMapping;

use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\MapToType;

#[MapFrom('nested')]
#[MapToType(
    key: 'muppet',
    map: [
        'rowlf' => Dog::class,
        'kermit' => Frog::class,
    ]
)]
interface Animal
{
    public function speak(): string;
}