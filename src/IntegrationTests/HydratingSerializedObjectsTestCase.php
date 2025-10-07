<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use EventSauce\ObjectHydrator\Fixtures\ClassThatCastsListsToBasedOnDocComments;
use EventSauce\ObjectHydrator\Fixtures\ClassThatCastsListsToDifferentTypes;
use EventSauce\ObjectHydrator\Fixtures\ClassThatCastsListToScalarType;
use EventSauce\ObjectHydrator\Fixtures\ClassThatHasMultipleCastersOnMapProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassThatHasMultipleCastersOnSingleProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassThatSpecifiesArraysWithDocComments;
use EventSauce\ObjectHydrator\Fixtures\ClassThatSpecifiesArrayWithIntegerKeys;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyCasting;
use EventSauce\ObjectHydrator\Fixtures\ClassWithStaticConstructor;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithIntegerEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithUnitEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\CustomEnum;
use EventSauce\ObjectHydrator\FixturesFor81\IntegerEnum;
use EventSauce\ObjectHydrator\FixturesFor81\OptionUnitEnum;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\TestCase;

abstract class HydratingSerializedObjectsTestCase extends TestCase
{
    abstract public function objectMapper(bool $serializeMapsAsObjects = false): ObjectMapper;

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function serializing_a_hydrated_class(string $className, array $input, array|null $types): void
    {
        $mapper = $this->objectMapper();

        $object = $mapper->hydrateObject($className, $input);
        $payload = $mapper->serializeObject($object);

        self::assertInstanceOf($className, $object);
        self::assertEquals($input, $payload);
        self::assertExpectedTypes($types ?? [], $object);
    }

    public function dataProvider(): iterable
    {
        yield 'class that casts a list to a scalar type' => [
            ClassThatCastsListToScalarType::class,
            ['test' => ['Frank']],
            ['test' => ['type' => 'list', 'values' => 'string']],
        ];

        yield 'class with list type resolve from doc comment' => [
            ClassThatCastsListsToBasedOnDocComments::class,
            [
                'short_list' => [
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
                'list' => [
                    ['snake_case' => 'Frank'],
                    ['snake_case' => 'Renske'],
                ],
            ],
            [
                'shortList' => [
                    'type' => 'list',
                    'values' => ClassWithCamelCaseProperty::class,
                ],
                'map' => [
                    'type' => 'map',
                    'values' => ClassWithCamelCaseProperty::class,
                ],
                'array' => [
                    'type' => 'array',
                    'values' => ClassWithCamelCaseProperty::class,
                ],
                'list' => [
                    'type' => 'list',
                    'values' => ClassWithCamelCaseProperty::class,
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
            [
                'first' => ['type' => 'list', 'values' => ClassWithCamelCaseProperty::class],
                'second' => ['type' => 'list', 'values' => ClassWithPropertyCasting::class],
            ],
        ];

        yield 'class with property type convertion' => [
            ClassWithPropertyCasting::class,
            ['age' => '34'],
            ['age' => ['type' => 'integer']],
        ];

        yield 'class with property mapped to a key' => [
            ClassThatHasMultipleCastersOnSingleProperty::class,
            ['child' => 12345],
            ['child' => ['type' => ClassWithStaticConstructor::class]],
        ];

        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            yield 'class with backed enum property' => [
                ClassWithEnumProperty::class,
                ['enum' => 'two'],
                ['enum' => ['type' => CustomEnum::class]],
            ];
            yield 'class with unit enum property' => [
                ClassWithUnitEnumProperty::class,
                ['enum' => 'OptionA'],
                ['enum' => ['type' => OptionUnitEnum::class]],
            ];
            yield 'class with integer enum property' => [
                ClassWithIntegerEnumProperty::class,
                ['enum' => 1],
                ['enum' => ['type' => IntegerEnum::class]],
            ];
        }
    }

    /**
     * @test
     * @dataProvider arrayDataProvider
     */
    public function serializes_associative_arrays_as_objects_based_on_configuration(
        string $class,
        bool $serializeMapsAsObjects,
        array $input,
        array $types,
    ): void {
        $mapper = $this->objectMapper(serializeMapsAsObjects: $serializeMapsAsObjects);

        $object = $mapper->hydrateObject($class, $input);
        $payload = $mapper->serializeObject($object);

        self::assertInstanceOf($class, $object);
        self::assertEquals($input, $payload);
        self::assertExpectedTypes($types, $object);
    }

    public function arrayDataProvider(): iterable
    {
        yield 'associative arrays as objects when casting enabled' => [
            ClassThatSpecifiesArraysWithDocComments::class,
            true,
            [
                'map_with_objects' => (object) [
                    'frank' => ['snake_case' => 'Frank'],
                    'renske' => ['snake_case' => 'Renske'],
                ],
                'map_with_scalars' => (object) ['one' => 1, 'two' => 2],
                'map_with_associative_arrays' => (object) [
                    'one' => ['key' => 'value'],
                    'two' => ['another_key' => 'another_value'],
                ],
                'list_without_type_hint' => ['Frank', 'Renske'],
                'list_with_type_hint' => ['Frank', 'Renske'],
                'method_map_with_objects' => (object) [
                    'frank' => ['snake_case' => 'Frank'],
                    'renske' => ['snake_case' => 'Renske'],
                ],
                'method_map_with_scalars' => (object) ['one' => 1, 'two' => 2 ],
                'method_map_with_associative_arrays' => (object) [
                    'one' => ['key' => 'value'],
                    'two' => ['another_key' => 'another_value'],
                ],
                'method_list_without_type_hint' => ['Frank', 'Renske'],
                'method_list_with_type_hint' => ['Frank', 'Renske'],
            ],
            [
                'mapWithObjects' => ['type' => 'map', 'values' => ClassWithCamelCaseProperty::class],
                'mapWithScalars' => ['type' => 'map', 'values' => 'integer'],
                'mapWithAssociativeArrays' => ['type' => 'map', 'values' => 'array'],
                'listWithoutTypeHint' => ['type' => 'list', 'values' => 'string'],
                'listWithTypeHint' => ['type' => 'list', 'values' => 'string'],
                'methodMapWithObjects' => ['type' => 'map', 'values' => ClassWithCamelCaseProperty::class],
                'methodMapWithScalars' => ['type' => 'map', 'values' => 'integer'],
                'methodMapWithAssociativeArrays' => ['type' => 'map', 'values' => 'array'],
                'methodListWithoutTypeHint' => ['type' => 'list', 'values' => 'string'],
                'methodListWithTypeHint' => ['type' => 'list', 'values' => 'string'],
            ]
        ];

        yield 'associative arrays as arrays when casting disabled' => [
            ClassThatSpecifiesArraysWithDocComments::class,
            false,
            [
                'map_with_objects' => [
                    'frank' => ['snake_case' => 'Frank'],
                    'renske' => ['snake_case' => 'Renske'],
                ],
                'map_with_scalars' => ['one' => 1, 'two' => 2],
                'map_with_associative_arrays' => [
                    'one' => ['key' => 'value'],
                    'two' => ['another_key' => 'another_value'],
                ],
                'list_without_type_hint' => ['Frank', 'Renske'],
                'list_with_type_hint' => ['Frank', 'Renske'],
                'method_map_with_objects' => [
                    'frank' => ['snake_case' => 'Frank'],
                    'renske' => ['snake_case' => 'Renske'],
                ],
                'method_map_with_scalars' => ['one' => 1, 'two' => 2 ],
                'method_map_with_associative_arrays' => [
                    'one' => ['key' => 'value'],
                    'two' => ['another_key' => 'another_value'],
                ],
                'method_list_without_type_hint' => ['Frank', 'Renske'],
                'method_list_with_type_hint' => ['Frank', 'Renske'],
            ],
            [
                'mapWithObjects' => ['type' => 'map', 'values' => ClassWithCamelCaseProperty::class],
                'mapWithScalars' => ['type' => 'map', 'values' => 'integer'],
                'mapWithAssociativeArrays' => ['type' => 'map', 'values' => 'array'],
                'listWithoutTypeHint' => ['type' => 'list', 'values' => 'string'],
                'listWithTypeHint' => ['type' => 'list', 'values' => 'string'],
                'methodMapWithObjects' => ['type' => 'map', 'values' => ClassWithCamelCaseProperty::class],
                'methodMapWithScalars' => ['type' => 'map', 'values' => 'integer'],
                'methodMapWithAssociativeArrays' => ['type' => 'map', 'values' => 'array'],
                'methodListWithoutTypeHint' => ['type' => 'list', 'values' => 'string'],
                'methodListWithTypeHint' => ['type' => 'list', 'values' => 'string'],
            ]
        ];

        yield 'non-sequential lists serialized as objects when casting enabled' => [
            ClassThatSpecifiesArrayWithIntegerKeys::class,
            true,
            [
                'array_with_integer_keys' => (object) [0 => 'zero', 2 => 'two'],
            ],
            [
                'arrayWithIntegerKeys' => ['type' => 'map', 'values' => 'string'],
            ],
        ];

        yield 'non-sequential lists serialized as arrays when casting disabled' => [
            ClassThatSpecifiesArrayWithIntegerKeys::class,
            false,
            [
                'array_with_integer_keys' => [0 => 'zero', 2 => 'two'],
            ],
            [
                'arrayWithIntegerKeys' => ['type' => 'map', 'values' => 'string'],
            ],
        ];

        yield 'sequential arrays serialized as arrays when casting enabled' => [
            ClassThatSpecifiesArrayWithIntegerKeys::class,
            true,
            [
                'array_with_integer_keys' => [0 => 'zero', 1 => 'one'],
            ],
            [
                'arrayWithIntegerKeys' => ['type' => 'list', 'values' => 'string'],
            ],
        ];

        yield 'sequential arrays serialized as arrays when casting disabled' => [
            ClassThatSpecifiesArrayWithIntegerKeys::class,
            false,
            [
                'array_with_integer_keys' => [0 => 'zero', 1 => 'one'],
            ],
            [
                'arrayWithIntegerKeys' => ['type' => 'list', 'values' => 'string'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider associativeArraysWithPropertySerializersDataProvider
     */
    public function serializes_associative_arrays_with_property_serializers_as_objects(
        bool $serializeMapsAsObjects,
        ClassThatHasMultipleCastersOnMapProperty $expectedObject,
        array $input,
    ): void {
        $mapper = $this->objectMapper($serializeMapsAsObjects);

        $object = $mapper->hydrateObject(ClassThatHasMultipleCastersOnMapProperty::class, $input);
        self::assertEquals($expectedObject, $object);

        $payload = $mapper->serializeObject($object);
        self::assertEquals($input, $payload);
    }

    private function associativeArraysWithPropertySerializersDataProvider(): iterable
    {
        yield 'associative arrays as objects' => [
            true,
            new ClassThatHasMultipleCastersOnMapProperty([
                'first_level' => [
                    'second_level' => ['one' => 1, 'two' => 2, 'three' => 3],
                ],
            ]),
            [
                'map' => (object) ['one' => 1, 'two' => 2, 'three' => 3],
            ],
        ];

        yield 'associative arrays as arrays' => [
            false,
            new ClassThatHasMultipleCastersOnMapProperty([
                'first_level' => [
                    'second_level' => ['one' => 1, 'two' => 2, 'three' => 3],
                ],
            ]),
            [
                'map' => ['one' => 1, 'two' => 2, 'three' => 3],
            ],
        ];
    }

    private static function assertExpectedTypes(array $types, object $object): void
    {
        foreach ($types as $property => $type) {
            $value = property_exists($object, $property) ? $object->{$property} : $object->$property();

            self::assertExpectedType($type['type'], $value);

            switch ($type['type']) {
                case 'map':
                case 'array':
                case 'list':
                    foreach ($value as $val) {
                        self::assertExpectedType($type['values'], $val);
                    }
                    break;
            }
        }
    }

    private static function assertExpectedType(string $expectedType, mixed $value): void
    {
        switch ($expectedType) {
            case 'array':
                self::assertIsArray($value);

                return;

            case 'list':
                self::assertArrayIsList($value);

                return;

            case 'map':
                self::assertArrayIsMap($value);

                return;

            case 'NULL':
                self::assertEquals(null, $value);

                return;
        }

        if (is_scalar($value)) {
            self::assertEquals($expectedType, gettype($value));

            return;
        }

        self::assertIsObject($value);
        self::assertInstanceOf($expectedType, $value);
    }

    private static function assertArrayIsList(mixed $value): void
    {
        self::assertIsArray($value);
        self::assertEquals(array_values($value), $value);
    }

    private static function assertArrayIsMap(mixed $value): void
    {
        self::assertIsArray($value);

        self::assertFalse(array_is_list($value));

        foreach (array_keys($value) as $key) {
            self::assertIsScalar($key);
        }
    }
}
