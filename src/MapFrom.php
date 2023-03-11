<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Attribute;
use function explode;
use function is_string;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
final class MapFrom
{
    /** @var array<string, array<string>> */
    public array $keys = [];

    /**
     * @param string|array<int|string, string> $keyOrMap
     */
    public function __construct(string|array $keyOrMap, public ?string $separator = null)
    {
        if (is_string($keyOrMap)) {
            $this->keys[$keyOrMap] = $this->separator ? explode($this->separator, $keyOrMap) : [$keyOrMap];
        } else {
            foreach ($keyOrMap as $index => $toKey) {
                $fromKey = is_string($index) ? $index : $toKey;
                $this->keys[$toKey] = $this->separator ? explode($this->separator, $fromKey) : [$fromKey];
            }
        }
    }
}
