<?php

namespace EventSauce\ObjectHydrator\Fixtures\CastToExpectedType;

final class AuthorId
{
    public function __construct(
        private string $id
    ) {}
    public static function fromString(string $id): static
    {
        return new static($id);
    }

    public function toString(): string
    {
        return $this->id;
    }
}
