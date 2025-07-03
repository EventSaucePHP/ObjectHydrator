<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;
use League\ConstructFinder\ConstructFinder;
use function class_exists;
use function spl_object_hash;
use function strpos;
use function unlink;

class ObjectMapperCodeGeneratorSerializationTest extends ObjectSerializationTestCase
{
    private DefinitionProvider $defaultDefinitionProvider;

    /**
     * @before
     */
    public function setupDefaultDefinitionProvider(): void
    {
        $this->defaultDefinitionProvider ??= new DefinitionProvider(
            keyFormatter: new KeyFormatterForSnakeCasing(),
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

    private function createDumpedObjectHydrator(
        string $directory,
        string $className,
        DefinitionProvider $definitionProvider,
        bool $omitNullValuesOnSerialization,
    ): ObjectMapper {
        if (class_exists($className, false)) {
            goto create_object_hydrator;
        }

        $classes = ConstructFinder::locatedIn($directory)->findClassNames();
        $interfaces = ConstructFinder::locatedIn($directory)->findInterfaceNames();
        $dumper = new ObjectMapperCodeGenerator($definitionProvider, $omitNullValuesOnSerialization);

        $dumpedDefinition = $dumper->dump(
            [...$classes, ...$interfaces],
            $className
        );
        $filename = __DIR__ . '/test' . (strpos($directory, '81') === false ? '80' : '81') . '.php';

        file_put_contents($filename, $dumpedDefinition);
        include $filename;
        unlink($filename);

        create_object_hydrator:
        /** @var ObjectMapper $objectHydrator */
        $objectHydrator = new $className();

        return $objectHydrator;
    }

    protected function createObjectHydrator(?DefinitionProvider $definitionProvider = null): ObjectMapper
    {
        $definitionProvider ??= $this->defaultDefinitionProvider;
        $className = 'AcmeCorp\\DumpedHydrator' . spl_object_hash($definitionProvider);

        return $this->createDumpedObjectHydrator(__DIR__ . '/Fixtures', $className, $definitionProvider, false);
    }

    public function objectMapper(bool $omitNullValuesOnSerialization = false): ObjectMapper
    {
        return $this->createDumpedObjectHydrator(
            __DIR__ . '/Fixtures',
            'AcmeCorp\\DumpedHydrator' . ($omitNullValuesOnSerialization ? 'SkippingNullOnSerialization' : ''),
            $this->defaultDefinitionProvider,
            $omitNullValuesOnSerialization,
        );
    }

    protected function objectMapperFor81(): ObjectMapper
    {
        return $this->createDumpedObjectHydrator(
            __DIR__ . '/FixturesFor81',
            'AcmeCorp\\DumpedHydratorFor81',
            $this->defaultDefinitionProvider,
            false,
        );
    }
}
