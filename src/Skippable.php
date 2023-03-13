<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class Skippable
{
    public static function skip(): Skippable
    {
        return new self();
    }
}