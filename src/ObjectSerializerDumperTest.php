<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use DateTime;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCustomDateTimeSerialization;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function unlink;

class ObjectSerializerDumperTest extends TestCase
{
    /**
     * @test
     */
    public function dumping_a_serializer(): void
    {
        $dumper = new ObjectSerializerDumper();
        $code = $dumper->dump([ClassWithCustomDateTimeSerialization::class], $className = 'SomeNamespace\\SomeClassName');
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
}
