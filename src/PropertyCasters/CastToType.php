<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;

use function settype;
use function var_dump;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class CastToType implements PropertyCaster, PropertySerializer
{
    public function __construct(
        private string $propertyType,
        private ?string $serializedType = null,
    ) {
    }

    public function cast(mixed $value, ObjectHydrator $hydrator): mixed
    {
        settype($value, $this->propertyType);

        return $value;
    }

    public function serialize(mixed $value, ObjectHydrator $hydrator): mixed
    {
        if ($this->serializedType) {
            settype($value, $this->serializedType);
        }

        return $value;
    }
}
