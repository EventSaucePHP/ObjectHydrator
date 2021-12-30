<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassThatContainsAnotherClass;
use EventSauce\ObjectHydrator\Fixtures\ClassWithFormattedDateTimeInput;
use EventSauce\ObjectHydrator\FixturesFor80\ClassWithComplexTypeThatIsMapped;
use League\ConstructFinder\Construct;
use League\ConstructFinder\ConstructFinder;
use PHPUnit\Framework\TestCase;

use function array_map;
use function file_put_contents;
use function is_file;
use function unlink;

class DefinitionDumperTest extends TestCase
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
        $dumper = new DefinitionDumper();

        $dumpedDefinition = $dumper->dump(
            [ClassWithFormattedDateTimeInput::class],
            $className = 'Dummy\\DummyDefinitionProvider'
        );
        file_put_contents(__DIR__ . '/test.php', $dumpedDefinition);
        include __DIR__ . '/test.php';
        /** @var DefinitionProvider $provider */
        $provider = new $className();
        $definition = $provider->provideDefinition(ClassWithFormattedDateTimeInput::class);

        self::assertInstanceOf(ClassDefinition::class, $definition);
    }

    /**
     * @test
     * @requires PHP >= 8.1
     */
    public function dumping_all_definitions_of_a_directory(): void
    {
        $dumper = new DefinitionDumper();
        $classes = ConstructFinder::locatedIn(__DIR__ . '/Fixtures')->findAll();
        $classes = array_map(function (Construct $c) { return $c->name(); }, $classes);

        $dumpedDefinition = $dumper->dump($classes, $className = 'Dummy\\AnotherDefinitionProvider');
        file_put_contents(__DIR__ . '/test.php', $dumpedDefinition);
        include __DIR__ . '/test.php';
        /** @var DefinitionProvider $provider */
        $provider = new $className();
        $definition = $provider->provideDefinition(ClassThatContainsAnotherClass::class);

        self::assertInstanceOf(ClassDefinition::class, $definition);
        self::assertEquals(ClassThatContainsAnotherClass::class, $definition->constructor);
    }
}
