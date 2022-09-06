<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use EventSauce\ObjectHydrator\PropertyCasters\CastToUuid;
use Ramsey\Uuid\UuidInterface;

#[ExampleData(['id' => '9f960d77-7c9b-4bfd-9fc4-62d141efc7e5', 'name' => 'Joe'])]
class ClassThatUsesMutipleCastersWithoutOptions
{
    public function __construct(
        #[CastToUuid]
        public UuidInterface $id,
        #[CastToLowerCase]
        public string $name,
    ) {
    }
}
