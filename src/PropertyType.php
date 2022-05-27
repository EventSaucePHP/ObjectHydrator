<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use function count;
use function enum_exists;
use function function_exists;

/**
 * @internal
 */
final class PropertyType
{
    /** @var ConcreteType[] */
    private array $concreteTypes;

    private bool $allowsNull;

    private function __construct(bool $allowsNull, ConcreteType ...$concreteTypes)
    {
        $this->concreteTypes = $concreteTypes;
        $this->allowsNull = $allowsNull;
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    /**
     * @return ConcreteType[]
     */
    public function concreteTypes(): array
    {
        return $this->concreteTypes;
    }

    public function containsBuiltInType(): bool
    {
        foreach ($this->concreteTypes as $type) {
            if ($type->isBuiltIn) {
                return true;
            }
        }

        return false;
    }

    public function canBeHydrated(): bool
    {
        return count($this->concreteTypes) === 1 && $this->concreteTypes[0]->isBuiltIn === false;
    }

    public static function fromReflectionType(
        ReflectionUnionType|ReflectionIntersectionType|ReflectionNamedType|null $type
    ): PropertyType {
        if ($type === null) {
            return static::mixed();
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            return static::fromCompositeType($type);
        }

        return static::fromNamedType($type);
    }

    public static function fromNamedType(ReflectionNamedType $type): static
    {
        $name = $type->getName();
        $canBeHydrated = $type->isBuiltin();

        if ( ! $canBeHydrated) {
            $reflectionClass = new ReflectionClass($name);
            $canBeHydrated = ! $reflectionClass->isUserDefined();
        }

        return new static($type->allowsNull(), new ConcreteType($type->getName(), $canBeHydrated));
    }

    public static function fromCompositeType(ReflectionIntersectionType|ReflectionUnionType $compositeType)
    {
        /** @var ReflectionNamedType[] $types */
        $types = $compositeType->getTypes();
        $resolvedTypes = [];

        foreach ($types as $type) {
            $resolvedTypes[] = new ConcreteType($type->getName(), $type->isBuiltin());
        }

        return new PropertyType($compositeType->allowsNull(), ...$resolvedTypes);
    }

    public static function mixed(): static
    {
        return new static(true, new ConcreteType('mixed', true));
    }

    public function firstTypeName(): ?string
    {
        return $this->concreteTypes[0]?->name;
    }

    public function isEnum(): bool
    {
        return count($this->concreteTypes) === 1
            && function_exists('enum_exists')
            && enum_exists($this->concreteTypes[0]->name);
    }
}
