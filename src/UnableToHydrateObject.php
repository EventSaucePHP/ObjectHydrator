<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use RuntimeException;
use Throwable;
use function implode;

final class UnableToHydrateObject extends RuntimeException
{
    private function __construct(
        string $message,
        ?Throwable $previous = null,
        private array $missingFields = [],
        private array $stack = [],
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function missingFields(): array
    {
        return $this->missingFields;
    }

    public function stack(): array
    {
        return $this->stack;
    }

    public static function dueToError(string $className, ?Throwable $previous = null, array $stack = []): static
    {
        $stackMessage = '';
        $previousMessage = $previous instanceof Throwable
            ? 'Caused by: ' . $previous->getMessage()
            : '';

        if ($previous instanceof UnableToHydrateObject) {
            $previousStack = $previous->stack();
            $stackMessage = empty($previousStack) ? '' : ' (property: ' . end($previousStack) . ')';
        }

        return new static("Unable to hydrate object: $className$stackMessage\n" . $previousMessage, $previous, [], $stack);
    }

    public static function dueToMissingFields(string $className, array $missingFields, array $stack = []): static
    {
        return new static("Unable to hydrate object: $className, missing fields: " . implode(', ', $missingFields), null, $missingFields, $stack);
    }

    public static function noHydrationDefined(string $className, array $stack = []): static
    {
        return new static("Unable to hydrate object: $className, no hydrator defined", stack: $stack);
    }

    public static function classIsNotInstantiable(string $className, array $stack = []): static
    {
        return new static("Unable to hydrate object: $className, is not instantiable", stack: $stack);
    }
}
