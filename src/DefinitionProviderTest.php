<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithFormattedDateTimeInput;
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
}
