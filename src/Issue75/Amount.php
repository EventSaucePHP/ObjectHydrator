<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Issue75;

class Amount
{
    public function __construct(
        public int $amount,
    ) {
    }
}