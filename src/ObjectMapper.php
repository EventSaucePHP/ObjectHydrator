<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

interface ObjectMapper
{
    /**
     * @template T of object
     *
     * @param class-string<T> $className
     * @param array<mixed>    $payload
     *
     * @return T
     *
     * @throws UnableToHydrateObject
     */
    public function hydrateObject(string $className, array $payload): object;

    /**
     * @template T
     *
     * @param class-string<T>        $className
     * @param iterable<array<mixed>> $payloads  ;
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
     * @param T               $object
     * @param class-string<T> $className
     */
    public function serializeObjectOfType(object $object, string $className): mixed;

    /**
     * @param iterable<object> $payloads
     *
     * @return IterableList<array<mixed>>
     *
     * @throws UnableToSerializeObject
     */
    public function serializeObjects(iterable $payloads): IterableList;
}
