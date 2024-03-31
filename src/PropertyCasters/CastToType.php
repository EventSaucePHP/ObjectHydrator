<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;
use function settype;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class CastToType implements PropertyCaster, PropertySerializer
{
    public function __construct(
        private string $propertyType,
        private ?string $serializedType = null,
    ) {
    }

    public function cast(mixed $value, ObjectMapper $hydrator, ?string $expectedTypeName): mixed
    {
        settype($value, $this->propertyType);

        return $value;
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        if ($this->serializedType) {
            settype($value, $this->serializedType);
        }

        return $value;
    }
}
