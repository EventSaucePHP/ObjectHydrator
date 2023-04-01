<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithFormattedDateTimeInput;
use EventSauce\ObjectHydrator\Fixtures\ClassWithInterfaceMapperSettings;
use EventSauce\ObjectHydrator\Fixtures\ClassWithParentMappingSettings;
use PHPUnit\Framework\TestCase;

class DefinitionProviderTest extends TestCase
{
    /**
     * @test
     */
    public function internal_classes_are_not_hydratable(): void
    {
        $provider = new DefinitionProvider();

        $definition = $provider->provideHydrationDefinition(ClassWithFormattedDateTimeInput::class);
        $dateTimeProperty = $definition->propertyDefinitions[0];

        self::assertFalse($dateTimeProperty->canBeHydrated);
    }

    /**
     * @test
     */
    public function mapper_settings_resolve_from_interfaces(): void
    {
        $provider = new DefinitionProvider();

        $definition = $provider->provideSerializationDefinition(ClassWithInterfaceMapperSettings::class);

        self::assertCount(1, $definition->properties);
        self::assertInstanceOf(PropertySerializationDefinition::class, $definition->properties[0]);
        self::assertSame(PropertySerializationDefinition::TYPE_PROPERTY, $definition->properties[0]->type);
    }

    /**
     * @test
     */
    public function mapper_settings_do_not_resolve_from_parent(): void
    {
        $provider = new DefinitionProvider();

        $definition = $provider->provideSerializationDefinition(ClassWithParentMappingSettings::class);

        self::assertCount(2, $definition->properties);
        self::assertInstanceOf(PropertySerializationDefinition::class, $definition->properties[0]);
        self::assertSame(PropertySerializationDefinition::TYPE_METHOD, $definition->properties[0]->type);
        self::assertInstanceOf(PropertySerializationDefinition::class, $definition->properties[1]);
        self::assertSame(PropertySerializationDefinition::TYPE_PROPERTY, $definition->properties[1]->type);
    }
}
