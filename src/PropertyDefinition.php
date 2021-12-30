<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

/**
 * @internal
 */
class PropertyDefinition
{
    public string $key;
    public string $property;
    public ?string $propertyCaster;
    public array $castingOptions;
    public bool $canBeHydrated;
    public bool $isEnum;
    public ?string $concreteTypeName;

    public function __construct(
        string $key,
        string $property,
        ?string $propertyCaster,
        array $castingOptions,
        bool $canBeHydrated,
        bool $isEnum,
        ?string $concreteTypeName,
    )
    {
        $this->concreteTypeName = $concreteTypeName;
        $this->isEnum = $isEnum;
        $this->canBeHydrated = $canBeHydrated;
        $this->castingOptions = $castingOptions;
        $this->propertyCaster = $propertyCaster;
        $this->property = $property;
        $this->key = $key;
    }
}
