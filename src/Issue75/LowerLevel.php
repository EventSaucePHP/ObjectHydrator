<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Issue75;

class LowerLevel
{
    public function __construct(
        public Amount $amount,
        public Slot $slot,
    ) {
    }
}