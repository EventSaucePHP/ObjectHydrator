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
        public string $payloadKey,
        public array $serializers
    )
    {
        $this->serializers = array_filter($this->serializers);
    }

    public function formattedAccessor(): string
    {
        return $this->accessorName . ($this->type === self::TYPE_METHOD ? '()' : '');
    }
}
