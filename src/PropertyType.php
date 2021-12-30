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
class PropertyType
{
    private array $concreteTypes;

    private function __construct(ConcreteType ...$concreteTypes)
    {
        $this->concreteTypes = $concreteTypes;
    }

    public function canBeHydrated(): bool
    {
        return count($this->concreteTypes) === 1 && $this->concreteTypes[0]->isBuiltIn === false;
    }

    public static function fromNamedType(ReflectionNamedType $type): self
    {
        $name = $type->getName();
        $canBeHydrated = $type->isBuiltin();

        if ( ! $canBeHydrated) {
            $reflectionClass = new ReflectionClass($name);
            $canBeHydrated = ! $reflectionClass->isUserDefined();
        }

        return new static(new ConcreteType($type->getName(), $canBeHydrated));
    }

    public static function fromCompositeType(ReflectionIntersectionType|ReflectionUnionType $type)
    {
        /** @var ReflectionNamedType[] $types */
        $types = $type->getTypes();
        $resolvedTypes = [];

        foreach ($types as $type) {
            $resolvedTypes[] = new ConcreteType($type->getName(), $type->isBuiltin());
        }

        return new PropertyType(...$resolvedTypes);
    }

    public static function mixed(): self
    {
        return new static(new ConcreteType('mixed', true));
    }

    public function firstTypeName(): ?string
    {
        $first = $this->concreteTypes[0] ?? null;

        return $first ? $first->name : null;
    }

    public function isEnum(): bool
    {
        return count($this->concreteTypes) === 1 && function_exists('enum_exists') && enum_exists(
                $this->concreteTypes[0]->name
            );
    }
}
