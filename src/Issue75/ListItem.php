<?php
declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Issue75;

class ListItem
{
    public function __construct(
        public string $value,
    ) {
    }
}