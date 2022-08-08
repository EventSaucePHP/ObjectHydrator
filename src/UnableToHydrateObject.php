<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use RuntimeException;
use Throwable;

use function implode;

final class UnableToHydrateObject extends RuntimeException
{
    private function __construct(string $message, ?Throwable $previous = null, private array $missingFields = [])
    {
        parent::__construct($message, 0, $previous);
    }

    public function missingFields(): array
    {
        return $this->missingFields;
    }

    public static function dueToError(string $className, ?Throwable $previous = null): static
    {
        return new static("Unable to hydrate object: $className\n" . ($previous?->getMessage() ?? ''), $previous);
    }

    public static function dueToMissingFields(string $className, array $missingFields): static
    {
        return new static("Unable to hydrate object: $className, missing fields: " . implode(', ', $missingFields), null, $missingFields);
    }
}
