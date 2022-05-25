<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassThatContainsAnotherClass;
use EventSauce\ObjectHydrator\Fixtures\ClassWithComplexTypeThatIsMapped;
use League\ConstructFinder\ConstructFinder;
use PHPUnit\Framework\TestCase;
use function file_put_contents;
use function is_file;
use function unlink;

class HydrationDefinitionDumperTest extends TestCase
{
    /**
     * @before
     * @after
     */
    public function removeTestFile(): void
    {
        is_file(__DIR__ . '/test.php') && unlink(__DIR__ . '/test.php');
    }

    /**
     * @test
     */
    public function dumping_a_definition(): void
    {
        $dumper = new HydrationDefinitionDumper();

        $dumpedDefinition = $dumper->dump(
            [ClassWithComplexTypeThatIsMapped::class],
            $className = 'Dummy\\DummyDefinitionProvider'
        );
        file_put_contents(__DIR__ . '/test.php', $dumpedDefinition);
        include __DIR__ . '/test.php';
        /** @var HydrationDefinitionProvider $provider */
        $provider = new $className();
        $definition = $provider->provideDefinition(ClassWithComplexTypeThatIsMapped::class);

        self::assertInstanceOf(ClassHydrationDefinition::class, $definition);
    }

    /**
     * @test
     * @requires PHP >= 8.1
     */
    public function dumping_all_definitions_of_a_directory(): void
    {
        $dumper = new HydrationDefinitionDumper();
        $classes = ConstructFinder::locatedIn(__DIR__ . '/Fixtures')->findAllNames();

        $dumpedDefinition = $dumper->dump($classes, $className = 'Dummy\\AnotherDefinitionProvider');
        file_put_contents(__DIR__ . '/test.php', $dumpedDefinition);
        include __DIR__ . '/test.php';
        /** @var HydrationDefinitionProvider $provider */
        $provider = new $className();
        $definition = $provider->provideDefinition(ClassThatContainsAnotherClass::class);

        self::assertInstanceOf(ClassHydrationDefinition::class, $definition);
        self::assertEquals(ClassThatContainsAnotherClass::class, $definition->constructor);
    }
}
