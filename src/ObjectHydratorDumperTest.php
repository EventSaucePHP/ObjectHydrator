<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;

use function is_file;
use function unlink;

class ObjectHydratorDumperTest extends ObjectHydratorTestCase
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
        $objectHydrator = $this->createObjectHydrator();

        $object = $objectHydrator->hydrateObject(ClassWithMappedStringProperty::class, ['my_name' => 'Frank de Jonge']);

        self::assertInstanceOf(ClassWithMappedStringProperty::class, $object);
    }

    protected function createObjectHydrator(): ObjectHydrator
    {
        return new ObjectHydrator(new ReflectionDefinitionProvider());
    }
}
