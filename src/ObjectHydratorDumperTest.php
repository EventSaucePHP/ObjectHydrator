<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;
use League\ConstructFinder\Construct;
use League\ConstructFinder\ConstructFinder;
use PHPUnit\Framework\TestCase;

use function array_map;
use function file_put_contents;
use function is_file;
use function unlink;

class ObjectHydratorDumperTest extends TestCase
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
    public function dumping_an_object_hydrator(): void
    {
        $classes = ConstructFinder::locatedIn(__DIR__ . '/Fixtures')->findAll();
        $classes = array_map(function (Construct $c) { return $c->name(); }, $classes);
        $dumper = new ObjectHydratorDumper();

        $dumpedDefinition = $dumper->dump(
            $classes,
            $className = 'Dummy\\DummyObjectHydrator'
        );
        file_put_contents(__DIR__ . '/test.php', $dumpedDefinition);
        include __DIR__ . '/test.php';
        /** @var ObjectHydrator $objectHydrator */
        $objectHydrator = new $className();
        $object = $objectHydrator->hydrateObject(ClassWithMappedStringProperty::class, ['my_name' => 'Frank de Jonge']);

        self::assertInstanceOf(ClassWithMappedStringProperty::class, $object);
    }
}
