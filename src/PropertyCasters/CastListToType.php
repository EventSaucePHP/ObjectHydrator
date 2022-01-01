<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\PropertyCasters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;

use function in_array;
use function settype;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class CastListToType implements PropertyCaster
{
    private bool $nativeType;

    public function __construct(
        private string $type
    ) {
        $this->nativeType = in_array($this->type, ["bool", "boolean", "int", "integer", "float", "double", "string", "array", "object", "null"]);
    }

    public function cast(mixed $value, ObjectHydrator $hydrator): mixed
    {
        $value = (array) $value;

        if ($this->nativeType) {
            return $this->castToNativeType($value);
        } else {
            return $this->castToObjectType($value, $hydrator);
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function castToNativeType(array $value): mixed
    {
        foreach ($value as $i => $item) {
            settype($item, $this->type);
            $value[$i] = $item;
        }

        return $value;
    }

    private function castToObjectType(array $value, ObjectHydrator $hydrator): array
    {
        foreach ($value as $i => $item) {
            $value[$i] = $hydrator->hydrateObject($this->type, $item);
        }

        return $value;
    }
}
