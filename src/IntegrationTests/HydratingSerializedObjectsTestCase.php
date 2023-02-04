<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use EventSauce\ObjectHydrator\Fixtures\ClassThatCastsListsToBasedOnDocComments;
use EventSauce\ObjectHydrator\Fixtures\ClassThatCastsListsToDifferentTypes;
use EventSauce\ObjectHydrator\Fixtures\ClassThatCastsListToScalarType;
use EventSauce\ObjectHydrator\Fixtures\ClassThatHasMultipleCastersOnSingleProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyCasting;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithIntegerEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithUnitEnumProperty;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\TestCase;

abstract class HydratingSerializedObjectsTestCase extends TestCase
{
    abstract public function objectMapper(): ObjectMapper;

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function serializing_a_hydrated_class(string $className, array $input): void
    {
        $mapper = $this->objectMapper();

        $object = $mapper->hydrateObject($className, $input);
        $payload = $mapper->serializeObject($object);

        self::assertInstanceOf($className, $object);
        self::assertEquals($input, $payload);
    }

    public function dataProvider(): iterable
    {
        yield 'class that casts a list to a scalar type' => [
            ClassThatCastsListToScalarType::class,
            ['test' => ['Frank']],
        ];

        yield 'class with list type resolve from doc comment' => [
            ClassThatCastsListsToBasedOnDocComments::class,
            [
                'list' => [
                    ['snake_case' => 'Frank'],
                    ['snake_case' => 'Renske'],
                ],
                'map' => [
                    'one' => ['snake_case' => 'Frank'],
                    'two' => ['snake_case' => 'Renske'],
                ],
                'array' => [
                    1 => ['snake_case' => 'Frank'],
                    '2' => ['snake_case' => 'Renske'],
                ],
            ],
        ];

        yield 'class with two lists' => [
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
            ],
        ];

        yield 'class with property type convertion' => [
            ClassWithPropertyCasting::class,
            ['age' => '34'],
        ];

        yield 'class with property mapped to a key' => [
            ClassThatHasMultipleCastersOnSingleProperty::class,
            ['child' => 12345],
        ];

        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            yield 'class with backed enum property' => [
                ClassWithEnumProperty::class,
                ['enum' => 'two'],
            ];
            yield 'class with unit enum property' => [
                ClassWithUnitEnumProperty::class,
                ['enum' => 'OptionA'],
            ];
            yield 'class with integer enum property' => [
                ClassWithIntegerEnumProperty::class,
                ['enum' => 1],
            ];
        }
    }
}
