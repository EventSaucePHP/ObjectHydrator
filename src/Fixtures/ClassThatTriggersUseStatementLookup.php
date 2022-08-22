<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use const JSON_PRETTY_PRINT;
use function json_encode;
use function sprintf;

class ClassThatTriggersUseStatementLookup
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public array $friends = [],
    ) {
    }

    public function name(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
