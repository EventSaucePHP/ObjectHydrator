<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Issue75;

class Slot
{
    public function __construct(
        public string $name,
        public string $value,
    ) {
    }
}