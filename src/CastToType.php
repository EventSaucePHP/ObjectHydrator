<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Attribute;

use function settype;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToType implements PropertyCaster
{
    /**
     * @var callable
     */
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function cast(mixed $value, array $options, ObjectHydrator $hydrator): mixed
    {
        settype($value, $this->type);

        return $value;
    }
}
