<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use function array_filter;

class PropertySerializationDefinition
{
    public const TYPE_METHOD = 'method';
    public const TYPE_PROPERTY = 'property';

    public function __construct(
        public string $type,
        public string $accessorName,
        public array $serializers,
        public PropertyType $propertyType,
        public bool $nullable,
        public array $keys = [],
        public ?string $typeSpecifier = null,
        public array $typeMap = [],
    ) {
        $this->serializers = array_filter($this->serializers);
    }

    public function formattedAccessor(): string
    {
        return $this->accessorName . ($this->type === self::TYPE_METHOD ? '()' : '');
    }

    public function isComplexType(): bool
    {
        return count($this->propertyType->concreteTypes()) > 1;
    }
}
