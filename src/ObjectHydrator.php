<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

/**
 * @template T
 */
interface ObjectHydrator
{
    /**
     * @param class-string<T> $className
     * @return T
     */
    public function hydrateObject(string $className, array $payload): object;
}
