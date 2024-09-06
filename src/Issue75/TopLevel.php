<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Issue75;

class TopLevel
{
    public function __construct(
        public int $number = 300,
        public ?LowerLevel $lower = null,
    ) {
    }
}