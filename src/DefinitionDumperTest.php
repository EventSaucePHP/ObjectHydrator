<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithComplexTypeThatIsMapped;
use EventSauce\ObjectHydrator\Fixtures\ClassWithEnumProperty;
use PHPUnit\Framework\TestCase;

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
            [ClassWithComplexTypeThatIsMapped::class],
            $className = 'Dummy\\DummyDefinitionProvider'
        );
        file_put_contents(__DIR__ . '/test.php', $dumpedDefinition);
        include __DIR__ . '/test.php';
        /** @var DefinitionProvider $provider */
        $provider = new $className();
        $definition = $provider->provideDefinition(ClassWithComplexTypeThatIsMapped::class);

        self::assertInstanceOf(ClassDefinition::class, $definition);
    }

    /**
     * @test
     */
    public function dumping_all_definitions_of_a_directory(): void
    {
        $dumper = new DefinitionDumper();
        $classes = ClassFinder::fromDirectory(__DIR__ . '/Fixtures')->classes();

        $dumpedDefinition = $dumper->dump($classes, $className = 'Dummy\\AnotherDefinitionProvider');
        file_put_contents(__DIR__ . '/test.php', $dumpedDefinition);
        include __DIR__ . '/test.php';
        /** @var DefinitionProvider $provider */
        $provider = new $className();
        $definition = $provider->provideDefinition(ClassWithEnumProperty::class);

        self::assertInstanceOf(ClassDefinition::class, $definition);
        self::assertEquals(ClassWithEnumProperty::class, $definition->constructor);
    }
}
