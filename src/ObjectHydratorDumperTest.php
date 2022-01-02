<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;
use League\ConstructFinder\Construct;
use League\ConstructFinder\ConstructFinder;
use function class_exists;
use function strpos;

class ObjectHydratorDumperTest extends ObjectHydratorTestCase
{
    /**
     * @before
     * @after
     */
    public function removeTestFile(): void
    {
        is_file(__DIR__ . '/test80.php') && unlink(__DIR__ . '/test80.php');
        is_file(__DIR__ . '/test81.php') && unlink(__DIR__ . '/test81.php');
    }

    /**
     * @test
     */
    public function dumping_an_object_hydrator(): void
    {
        $objectHydrator = $this->createObjectHydrator();

        $object = $objectHydrator->hydrateObject(ClassWithMappedStringProperty::class, ['my_name' => 'Frank de Jonge']);

        self::assertInstanceOf(ClassWithMappedStringProperty::class, $object);
    }

    private function createDumpedObjectHydrator(string $directory, string $className): ObjectHydrator
    {
        if (class_exists($className, false)) {
            goto create_object_hydrator;
        }

        $classes = ConstructFinder::locatedIn($directory)->findClasses();
        $classes = array_map(function (Construct $c) {
            return $c->name();
        }, $classes);
        $dumper = new ObjectHydratorDumper();

        $dumpedDefinition = $dumper->dump(
            $classes,
            $className
        );
        $filename = __DIR__ . '/test' . (strpos($directory, '81') === false ? '80' : '81') . '.php';

        file_put_contents($filename, $dumpedDefinition);
        include $filename;

        create_object_hydrator:
        /** @var ObjectHydrator $objectHydrator */
        $objectHydrator = new $className();

        return $objectHydrator;
    }

    protected function createObjectHydrator(): ObjectHydrator
    {
        return $this->createDumpedObjectHydrator(__DIR__ . '/Fixtures', 'AcmeCorp\\DumpedHydrator');
    }

    protected function createObjectHydratorFor81(): ObjectHydrator
    {
        return $this->createDumpedObjectHydrator(__DIR__ . '/FixturesFor81', 'AcmeCorp\\DumpedHydratorFor81');
    }
}
