<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Attribute;
use function is_int;
use function is_string;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class MapFrom
{
    /** @var array<string, string> */
    public array $keys = [];

    public function __construct(string|array $keyOrMap)
    {
        if (is_string($keyOrMap)) {
            $this->keys[$keyOrMap] = $keyOrMap;
        } else {
            foreach ($keyOrMap as $index => $key) {
                $this->keys[is_int($index) ? $key : $index] = $key;
            }
        }
    }
}
