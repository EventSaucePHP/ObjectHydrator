<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use RuntimeException;
use Throwable;

final class UnableToHydrateObject extends RuntimeException
{
    private function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function dueToError(string $className, ?Throwable $previous = null): static
    {
        return new static("Unable to hydrate object: $className", $previous);
    }
}
