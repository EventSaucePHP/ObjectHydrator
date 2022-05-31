<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use EventSauce\ObjectHydrator\Fixtures\ClassThatCastsListsToDifferentTypes;
use EventSauce\ObjectHydrator\Fixtures\ClassThatHasMultipleCastersOnSingleProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyCasting;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithIntegerEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithUnitEnumProperty;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\TestCase;

abstract class HydratingSerializedObjectsTestCase extends TestCase
{
    abstract public function objectHydrator(): ObjectMapper;

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function serializing_a_hydrated_class(string $className, array $input): void
    {
        $hydrator = $this->objectHydrator();

        $object = $hydrator->hydrateObject($className, $input);
        $payload = $hydrator->serializeObject($object);

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

        yield "class with property type convertion" => [
            ClassWithPropertyCasting::class,
            ['age' => '34']
        ];

        yield "class with property mapped to a key" => [
            ClassThatHasMultipleCastersOnSingleProperty::class,
            ['child' => 12345],
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
