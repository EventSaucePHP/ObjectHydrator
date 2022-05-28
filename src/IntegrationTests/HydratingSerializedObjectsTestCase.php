<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use EventSauce\ObjectHydrator\Fixtures\ClassThatCastsListsToDifferentTypes;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithIntegerEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithUnitEnumProperty;
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\ObjectSerializer;
use PHPUnit\Framework\TestCase;

abstract class HydratingSerializedObjectsTestCase extends TestCase
{
    abstract public function objectSerializer(): ObjectSerializer;
    abstract public function objectHydrator(): ObjectHydrator;

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function serializing_a_hydrated_class(string $className, array $input): void
    {
        $hydrator = $this->objectHydrator();
        $serializer = $this->objectSerializer();

        $object = $hydrator->hydrateObject($className, $input);
        $payload = $serializer->serializeObject($object);

        self::assertInstanceOf($className, $object);
        self::assertEquals($input, $payload);
    }

    public function dataProvider(): iterable
    {
        yield "class with two lists" => [
            ClassThatCastsListsToDifferentTypes::class,
            [
                'first' => [
                    ['snake_case' => 'Frank'],
                    ['snake_case' => 'Renske'],
                ],
                'second' => [
                    ['age' => 34],
                    ['age' => 31],
                ],
            ]
        ];

        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            yield "class with backed enum property" => [
                ClassWithEnumProperty::class,
                ['enum' => 'two'],
            ];
            yield "class with unit enum property" => [
                ClassWithUnitEnumProperty::class,
                ['enum' => 'OptionA'],
            ];
            yield "class with integer enum property" => [
                ClassWithIntegerEnumProperty::class,
                ['enum' => 1],
            ];
        }
    }
}