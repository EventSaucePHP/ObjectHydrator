<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use function strtolower;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class CastToLowerCase implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator, ?string $expectedTypeName): mixed
    {
        return strtolower((string) $value);
    }
}
