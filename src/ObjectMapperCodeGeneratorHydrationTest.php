<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;
use League\ConstructFinder\ConstructFinder;
use function array_unique;
use function class_exists;
use function spl_object_hash;
use function strpos;
use function unlink;
use function var_dump;

class ObjectMapperCodeGeneratorHydrationTest extends ObjectHydrationTestCase
{
    private DefinitionProvider $defaultDefinitionProvider;

    /**
     * @before
     */
    public function setupDefaultDefinitionProvider(): void
    {
        $this->defaultDefinitionProvider ??= new DefinitionProvider(
            keyFormatter: new KeyFormatterWithoutConversion(),
        );
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

    private function createDumpedObjectHydrator(string $directory, string $className, DefinitionProvider $definitionProvider): ObjectMapper
    {
        if (class_exists($className, false)) {
            goto create_object_hydrator;
        }

        $classes = ConstructFinder::locatedIn($directory)->findClassNames();
        $interfaces = ConstructFinder::locatedIn($directory)->findInterfaceNames();
        $dumper = new ObjectMapperCodeGenerator($definitionProvider);

        $dumpedDefinition = $dumper->dump(
            array_unique([...$interfaces, ...$classes]),
            $className
        );
        $filename = __DIR__ . '/test' . (strpos($directory, '81') === false ? '80' : '81') . '.php';

        file_put_contents($filename, $dumpedDefinition);
        include $filename;
//        unlink($filename);

        create_object_hydrator:
        /** @var ObjectMapper $objectHydrator */
        $objectHydrator = new $className();

        return $objectHydrator;
    }

    protected function createObjectHydrator(DefinitionProvider $definitionProvider = null): ObjectMapper
    {
        $definitionProvider ??= $this->defaultDefinitionProvider;
        $className = 'AcmeCorp\\DumpedHydrator' . spl_object_hash($definitionProvider);

        return $this->createDumpedObjectHydrator(__DIR__ . '/Fixtures', $className, $definitionProvider);
    }

    protected function createObjectHydratorFor81(): ObjectMapper
    {
        return $this->createDumpedObjectHydrator(__DIR__ . '/FixturesFor81', 'AcmeCorp\\DumpedHydratorFor81', $this->defaultDefinitionProvider);
    }
}
