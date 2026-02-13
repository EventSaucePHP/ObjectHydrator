<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;
use function class_exists;
use function spl_object_hash;
use function strpos;
use function unlink;

class ObjectMapperCodeGeneratorSerializationWithOutFeedingAllClassesTest extends ObjectSerializationTestCase
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
    ): ObjectMapper
    {
        return new class ($directory, $className, $definitionProvider, $omitNullValuesOnSerialization) implements ObjectMapper {
            public function __construct(
                private string $directory,
                private string $className,
                private DefinitionProvider $definitionProvider,
                private bool $omitNullValuesOnSerialization,
            ) {}

            public function hydrateObject(string $className, array $payload): object
            {
                return $this->generateObjectMapper($className)->hydrateObject($className, $payload);
            }

            public function hydrateObjects(string $className, iterable $payloads): IterableList
            {
                return $this->generateObjectMapper($className)->hydrateObjects($className, $payloads);
            }

            public function serializeObject(object $object): mixed
            {
                return $this->generateObjectMapper($object::class)->serializeObject($object);
            }

            public function serializeObjectOfType(object $object, string $className): mixed
            {
                return $this->generateObjectMapper($className)->serializeObjectOfType($object, $className);
            }

            public function serializeObjects(iterable $payloads): IterableList
            {
                return new IterableList($this->doSerializeObjects($payloads));
            }

            private function doSerializeObjects(iterable $objects): \Generator
            {
                foreach ($objects as $index => $object) {
                    yield $index => $this->serializeObject($object);
                }
            }

            private function generateObjectMapper(string $className): ObjectMapper
            {
                return $this->createDumpedObjectHydrator(
                    $this->directory,
                    $this->className . 'For' . str_replace('\\', '', $className),
                    $this->definitionProvider,
                    $this->omitNullValuesOnSerialization,
                    $className,
                );
            }

            private function createDumpedObjectHydrator(
                string $directory,
                string $className,
                DefinitionProvider $definitionProvider,
                bool $omitNullValuesOnSerialization,
                string ...$classes,
            ): ObjectMapper {
                if (class_exists($className, false)) {
                    goto create_object_hydrator;
                }

                $dumper = new ObjectMapperCodeGenerator($definitionProvider, $omitNullValuesOnSerialization);

                $dumpedDefinition = $dumper->dump(
                    $classes,
                    $className,
                );
                $filename = __DIR__ . '/smart-test' . (strpos($directory, '81') === false ? '80' : '81') . '.php';

                file_put_contents($filename, $dumpedDefinition);
                include $filename;
                unlink($filename);

                create_object_hydrator:
                /** @var ObjectMapper $objectHydrator */
                $objectHydrator = new $className();

                return $objectHydrator;
            }
        };
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
            'AcmeCorp\\SmartHydrator' . ($omitNullValuesOnSerialization ? 'SkippingNullOnSerialization' : ''),
            $this->defaultDefinitionProvider,
            $omitNullValuesOnSerialization,
        );
    }

    protected function objectMapperFor81(): ObjectMapper
    {
        return $this->createDumpedObjectHydrator(
            __DIR__ . '/FixturesFor81',
            'AcmeCorp\\SmartHydratorFor81',
            $this->defaultDefinitionProvider,
            false,
        );
    }

    /**
     * @test
     */
    final public function serializing_a_class_with_polymorphism(): void
    {
        $this->markTestSkipped('This specific test isn\'t supported in this specific scenario');
    }

    /**
     * @test
     */
    final public function serializing_an_interface_with_polymorphism(): void
    {
        $this->markTestSkipped('This specific test isn\'t supported in this specific scenario');
    }

    /**
     * @test
     */
    final public function serializing_a_list_of_custom_objects(): void
    {
        $this->markTestSkipped('This specific test isn\'t supported in this specific scenario');
    }
}
