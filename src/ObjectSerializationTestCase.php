<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use DateTime;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\Fixtures\ClassReferencedByUnionOne;
use EventSauce\ObjectHydrator\Fixtures\ClassReferencedByUnionTwo;
use EventSauce\ObjectHydrator\Fixtures\ClassThatOmitsPublicMethods;
use EventSauce\ObjectHydrator\Fixtures\ClassThatOmitsPublicProperties;
use EventSauce\ObjectHydrator\Fixtures\ClassThatOmitsSpecificMethodsAndProperties;
use EventSauce\ObjectHydrator\Fixtures\ClassThatRenamesInputForClassWithMultipleProperties;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCasePublicMethod;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCustomDateTimeSerialization;
use EventSauce\ObjectHydrator\Fixtures\ClassWithListOfObjects;
use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithMultipleProperties;
use EventSauce\ObjectHydrator\Fixtures\ClassWithUnionProperty;
use EventSauce\ObjectHydrator\Fixtures\TypeMapping\ClassThatMapsTypes;
use EventSauce\ObjectHydrator\Fixtures\TypeMapping\Dog;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\CustomEnum;
use PHPUnit\Framework\TestCase;
use function array_keys;
use function PHPUnit\Framework\assertEquals;

abstract class ObjectSerializationTestCase extends TestCase
{
    abstract public function objectHydrator(): ObjectMapper;

    abstract protected function objectHydratorFor81(): ObjectMapper;

    /**
     * @test
     */
    public function serializing_a_class_with_polymorphism(): void
    {
        $serializer = $this->objectHydrator();
        $object = new ClassThatMapsTypes(new Dog('Rover'));

        /** @var array $payload */
        $payload = $serializer->serializeObject($object);
        self::assertIsArray($payload);
        self::assertEquals('dog', $payload['child']['animal'] ?? '');
        self::assertEquals('Rover', $payload['child']['name'] ?? '');
    }

    /**
     * @test
     */
    public function serializing_an_object_with_a_public_property(): void
    {
        $serializer = $this->objectHydrator();
        $object = new ClassWithCamelCaseProperty('some_property');

        $payload = $serializer->serializeObject($object);

        self::assertEquals(['snake_case' => 'some_property'], $payload);
    }

    /**
     * @test
     */
    public function excluding_public_methods_through_object_settings(): void
    {
        $object = new ClassThatOmitsPublicMethods();

        $payload = $this->objectHydrator()->serializeObject($object);

        assertEquals(1, count(array_keys($payload)));
        assertEquals(['included' => 'included!'], $payload);
    }

    /**
     * @test
     */
    public function excluding_public_properties_through_object_settings(): void
    {
        $object = new ClassThatOmitsPublicProperties();

        $payload = $this->objectHydrator()->serializeObject($object);

        assertEquals(1, count(array_keys($payload)));
        assertEquals(['included' => 'included!'], $payload);
    }

    /**
     * @test
     */
    public function excluding_public_properties_through_annotations(): void
    {
        $object = new ClassThatOmitsSpecificMethodsAndProperties();

        $payload = $this->objectHydrator()->serializeObject($object);

        assertEquals(2, count(array_keys($payload)));
        assertEquals([
            'included_property' => 'included property',
            'included_method_field' => 'included method value',
        ], $payload);
    }

    /**
     * @test
     */
    public function serializing_an_object_with_a_public_method(): void
    {
        $serializer = $this->objectHydrator();
        $object = new ClassWithCamelCasePublicMethod('some_property');

        $payload = $serializer->serializeObject($object);

        self::assertEquals(['camel_case' => 'some_property'], $payload);
    }

    /**
     * @test
     */
    public function serializing_a_list_of_custom_objects(): void
    {
        $serializer = $this->objectHydrator();
        $object = new ClassWithListOfObjects([
            new ClassWithCamelCasePublicMethod('first_element'),
            new ClassWithCamelCasePublicMethod('second_element'),
        ]);

        $payload = $serializer->serializeObject($object);

        self::assertEquals(['children' => [
            ['camel_case' => 'first_element'],
            ['camel_case' => 'second_element'],
        ]], $payload);
    }

    /**
     * @test
     */
    public function serializing_a_list_of_internal_objects(): void
    {
        $serializer = $this->objectHydrator();
        $now = new DateTimeImmutable();
        $nowFormatted = $now->format('Y-m-d H:i:s.uO');
        $object = new ClassWithListOfObjects([$now]);

        $payload = $serializer->serializeObject($object);

        self::assertEquals(['children' => [$nowFormatted]], $payload);
    }

    /**
     * @test
     */
    public function serializing_using_custom_date_time_formats(): void
    {
        $serializer = $this->objectHydrator();
        $object = new ClassWithCustomDateTimeSerialization(
            promotedPublicProperty: DateTimeImmutable::createFromFormat('!Y-m-d', '1987-11-24'),
            regularPublicProperty: DateTimeImmutable::createFromFormat('!Y-m-d', '1987-11-25'),
            getterProperty: DateTime::createFromFormat('!Y-m-d', '1987-11-26')
        );

        $payload = $serializer->serializeObject($object);

        self::assertEquals([
            'promoted_public_property' => '24-11-1987',
            'regular_public_property' => '25-11-1987',
            'getter_property' => '26-11-1987',
        ], $payload);
    }

    /**
     * @test
     * @requires PHP >= 8.1
     */
    public function serializing_a_class_with_an_enum(): void
    {
        $serializer = $this->objectHydratorFor81();
        $object = new ClassWithEnumProperty(CustomEnum::VALUE_ONE);

        $payload = $serializer->serializeObject($object);

        self::assertEquals(['enum' => 'one'], $payload);
    }

    /**
     * @test
     */
    public function serializing_a_class_with_a_union(): void
    {
        $serializer = $this->objectHydrator();
        $object1 = new ClassWithUnionProperty(
            new ClassReferencedByUnionOne(1234),
            'name',
            new ClassReferencedByUnionOne(1234),
            null,
            null,
        );
        $object2 = new ClassWithUnionProperty(
            new ClassReferencedByUnionTwo('name'),
            1234,
            2345,
            null,
            new ClassReferencedByUnionOne(1234),
        );

        $payload1 = $serializer->serializeObject($object1);
        $payload2 = $serializer->serializeObject($object2);

        self::assertEquals([
            'union' => ['number' => 1234],
            'built_in_union' => 'name',
            'mixed_union' => ['number' => 1234],
            'nullable_mixed_union' => null,
            'nullable_via_union' => null,
        ], $payload1);
        self::assertEquals([
            'union' => ['text' => 'name'],
            'built_in_union' => 1234,
            'mixed_union' => 2345,
            'nullable_mixed_union' => null,
            'nullable_via_union' => ['number' => 1234],
        ], $payload2);
    }

    /**
     * @test
     */
    public function mapping_to_a_different_key(): void
    {
        $serializer = $this->objectHydrator();
        $object = new ClassWithMappedStringProperty(name: 'Frank');

        $payload = $serializer->serializeObject($object);
        self::assertEquals(['my_name' => 'Frank'], $payload);
    }

    /**
     * @test
     */
    public function mapping_to_multiple_keys(): void
    {
        $serializer = $this->objectHydrator();
        $object = new ClassThatRenamesInputForClassWithMultipleProperties(
            new ClassWithMultipleProperties(age: 34, name: 'Frank')
        );

        $payload = $serializer->serializeObject($object);
        self::assertEquals(['mapped_age' => 34, 'name' => 'Frank'], $payload);
    }
}
