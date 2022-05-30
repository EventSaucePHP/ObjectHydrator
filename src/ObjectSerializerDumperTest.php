<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use DateTime;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCustomDateTimeSerialization;
use League\ConstructFinder\ConstructFinder;

use function class_exists;
use function file_put_contents;
use function unlink;

class ObjectSerializerDumperTest extends ObjectSerializerTestCase
{
    /**
     * @test
     */
    public function dumping_a_serializer(): void
    {
        $dumper = new ObjectSerializerDumper();
        $code = $dumper->dump([ClassWithCustomDateTimeSerialization::class],
                              $className = 'SomeNamespace\\SomeClassName');
        file_put_contents(__DIR__ . '/testSerializationDumper.php', $code);
        include __DIR__ . '/testSerializationDumper.php';
        unlink(__DIR__ . '/testSerializationDumper.php');

        $serializer = new $className;
        $nowImmutable = DateTimeImmutable::createFromFormat('Y-m-d', '1987-11-24');
        $nowMutable = DateTime::createFromFormat('Y-m-d', '1987-11-24');
        $object = new ClassWithCustomDateTimeSerialization($nowImmutable, $nowImmutable, $nowMutable);

        $payload = $serializer->serializeObject($object);
        $expectedPayload = [
            'promoted_public_property' => '24-11-1987',
            'regular_public_property' => '24-11-1987',
            'getter_property' => '24-11-1987',
        ];

        self::assertEquals($expectedPayload, $payload);
    }

    private function createDumpedObjectSerializer(
        string $directory,
        string $className,
        SerializationDefinitionProviderUsingReflection $definitionProvider
    ): ObjectSerializer {
        if (class_exists($className, false)) {
            goto create_object_serializer;
        }

        $classes = ConstructFinder::locatedIn($directory)->findClassNames();
        $dumper = new ObjectSerializerDumper($definitionProvider);

        $dumpedDefinition = $dumper->dump(
            $classes,
            $className
        );
        $filename = __DIR__ . '/test' . (! str_contains($directory, '81') ? '80' : '81') . '.php';

        file_put_contents($filename, $dumpedDefinition);
        include $filename;
        unlink($filename);

        create_object_serializer:
        /** @var ObjectSerializer $objectSerializer */
        $objectSerializer = new $className();

        return $objectSerializer;
    }

    public function objectSerializer(): ObjectSerializer
    {
        $definitionProvider = new SerializationDefinitionProviderUsingReflection();
        $className = 'AcmeCorp\\DumpedSerializer';

        return $this->createDumpedObjectSerializer(__DIR__ . '/Fixtures', $className, $definitionProvider);
    }

    protected function objectSerializerFor81(): ObjectSerializer
    {
        $definitionProvider = new SerializationDefinitionProviderUsingReflection();
        $className = 'AcmeCorp\\DumpedSerializerFor81';

        return $this->createDumpedObjectSerializer(__DIR__ . '/FixturesFor81', $className, $definitionProvider);
    }
}
