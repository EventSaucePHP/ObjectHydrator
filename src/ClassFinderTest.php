<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassThatImplementsAnInterface;
use EventSauce\ObjectHydrator\Fixtures\InterfaceToFilterOn;
use PHPUnit\Framework\TestCase;

use function class_exists;

class ClassFinderTest extends TestCase
{
    /**
     * @test
     */
    public function finding_classes(): void
    {
        $classes = ClassFinder::fromDirectory(__DIR__ . '/Fixtures')->classes();

        self::assertNotEmpty($classes);

        foreach ($classes as $className) {
            self::assertTrue(class_exists($className, true));
        }
    }

    /**
     * @test
     */
    public function finding_classes_that_implement_an_interface(): void
    {
        $classes = ClassFinder::fromDirectory(__DIR__ . '/Fixtures')
            ->thatImplement(InterfaceToFilterOn::class)
            ->classes();

        self::assertCount(1, $classes);
        self::assertEquals(ClassThatImplementsAnInterface::class, $classes[0] ?? '');
    }
}
