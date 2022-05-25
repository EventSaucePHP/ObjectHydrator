<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;
use League\ConstructFinder\ConstructFinder;
use function class_exists;
use function spl_object_hash;
use function strpos;
use function unlink;

class ObjectHydratorDumperTest extends ObjectHydratorTestCase
{
    private ReflectionHydrationDefinitionProvider $defaultDefintionProvider;

    /**
     * @before
     */
    public function setupDefaultDefinitionProvider(): void
    {
        $this->defaultDefintionProvider = new ReflectionHydrationDefinitionProvider();
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

    private function createDumpedObjectHydrator(string $directory, string $className, HydrationDefinitionProvider $definitionProvider): ObjectHydrator
    {
        if (class_exists($className, false)) {
            goto create_object_hydrator;
        }

        $classes = ConstructFinder::locatedIn($directory)->findClassNames();
        $dumper = new ObjectHydratorDumper($definitionProvider);

        $dumpedDefinition = $dumper->dump(
            $classes,
            $className
        );
        $filename = __DIR__ . '/test' . (strpos($directory, '81') === false ? '80' : '81') . '.php';

        file_put_contents($filename, $dumpedDefinition);
        include $filename;
        unlink($filename);

        create_object_hydrator:
        /** @var ObjectHydrator $objectHydrator */
        $objectHydrator = new $className();

        return $objectHydrator;
    }

    protected function createObjectHydrator(HydrationDefinitionProvider $definitionProvider = null): ObjectHydrator
    {
        $definitionProvider ??= $this->defaultDefintionProvider;
        $className = 'AcmeCorp\\DumpedHydrator' . spl_object_hash($definitionProvider);

        return $this->createDumpedObjectHydrator(__DIR__ . '/Fixtures', $className, $definitionProvider);
    }

    protected function createObjectHydratorFor81(): ObjectHydrator
    {
        return $this->createDumpedObjectHydrator(__DIR__ . '/FixturesFor81', 'AcmeCorp\\DumpedHydratorFor81', $this->defaultDefintionProvider);
    }
}
