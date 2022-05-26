<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class PropertySerializationDefinition
{
    public const TYPE_METHOD = 'method';
    public const TYPE_PROPERTY = 'property';

    public function __construct(
        public string $type,
        public string $accessorName,
        public string $payloadKey,
        public ?array $serializer
    )
    {
    }

    public function formattedAccessor(): string
    {
        return $this->accessorName . ($this->type === self::TYPE_METHOD ? '()' : '');
    }
}
