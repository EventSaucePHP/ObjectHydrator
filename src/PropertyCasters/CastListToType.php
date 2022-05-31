<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;
use function assert;
use function in_array;
use function is_array;
use function settype;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class CastListToType implements PropertyCaster, PropertySerializer
{
    public const NATIVE_TYPES = ['bool', 'boolean', 'int', 'integer', 'float', 'double', 'string', 'array', 'object', 'null'];

    private bool $nativePropertyType;

    public function __construct(
        private string $propertyType,
        private ?string $serializedType = null,
    ) {
        $this->nativePropertyType = in_array($this->propertyType, self::NATIVE_TYPES);
    }

    public function cast(mixed $value, ObjectHydrator $hydrator): mixed
    {
        assert(is_array($value), 'value is expected to be an array');

        if ($this->nativePropertyType) {
            return $this->castToNativeType($value, $this->propertyType);
        } else {
            return $this->castToObjectType($value, $hydrator);
        }
    }

    /**
     * @param mixed $value
     */
    private function castToNativeType(array $value, string $type): mixed
    {
        foreach ($value as $i => $item) {
            settype($item, $type);
            $value[$i] = $item;
        }

        return $value;
    }

    private function castToObjectType(array $value, ObjectHydrator $hydrator): array
    {
        foreach ($value as $i => $item) {
            $value[$i] = $hydrator->hydrateObject($this->propertyType, $item);
        }

        return $value;
    }

    public function serialize(mixed $value, ObjectHydrator $hydrator): mixed
    {
        assert(is_array($value), 'value should be an array');

        if ($this->serializedType !== null) {
            return $this->castToNativeType($value, $this->serializedType);
        }

        if ($this->nativePropertyType) {
            return $value;
        }

        foreach ($value as $i => $item) {
            $value[$i] = $hydrator->serializeObject($item);
        }

        return $value;
    }
}
