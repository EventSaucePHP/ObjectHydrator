<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface ObjectMapper
{
    /**
     * @template T
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws UnableToHydrateObject
     */
    public function hydrateObject(string $className, array $payload): object;

    /**
     * @template T
     *
     * @param class-string<T> $className
     * @param iterable<array> $payloads  ;
     *
     * @return IterableList<T>
     *
     * @throws UnableToHydrateObject
     */
    public function hydrateObjects(string $className, iterable $payloads): IterableList;

    /**
     * @throws UnableToSerializeObject
     */
    public function serializeObject(object $object): mixed;

    /**
     * @template T
     *
     * @param class-string<T> $className
     * @param iterable<array> $payloads  ;
     *
     * @return IterableList<T>
     *
     * @throws UnableToSerializeObject
     */
    public function serializeObjects(iterable $payloads): IterableList;
}
