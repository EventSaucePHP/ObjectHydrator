<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\CastersOnClasses\ClassWithClassLevelMapFrom;
use EventSauce\ObjectHydrator\Fixtures\CastersOnClasses\ClassWithClassLevelMapFromMultiple;
use EventSauce\ObjectHydrator\Fixtures\ClassThatCastsListsToDifferentTypes;
use EventSauce\ObjectHydrator\Fixtures\ClassThatContainsAnotherClass;
use EventSauce\ObjectHydrator\Fixtures\ClassThatHasMultipleCastersOnSingleProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassThatRenamesInputForClassWithMultipleProperties;
use EventSauce\ObjectHydrator\Fixtures\ClassThatTriggersUseStatementLookup;
use EventSauce\ObjectHydrator\Fixtures\ClassThatUsesClassWithMultipleProperties;
use EventSauce\ObjectHydrator\Fixtures\ClassThatUsesMutipleCastersWithoutOptions;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithComplexTypeThatIsMapped;
use EventSauce\ObjectHydrator\Fixtures\ClassWithDefaultValueProvidingCaster;
use EventSauce\ObjectHydrator\Fixtures\ClassWithDocblockAndArrayFollowingScalar;
use EventSauce\ObjectHydrator\Fixtures\ClassWithDefaultValue;
use EventSauce\ObjectHydrator\Fixtures\ClassWithDocblockArrayVariants;
use EventSauce\ObjectHydrator\Fixtures\ClassWithFormattedDateTimeInput;
use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithNotCastedDateTimeInput;
use EventSauce\ObjectHydrator\Fixtures\ClassWithNullableInput;
use EventSauce\ObjectHydrator\Fixtures\ClassWithNullableProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertiesWithDefaultValues;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyCasting;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyMappedFromNestedKey;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCasting;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCastingToClasses;
use EventSauce\ObjectHydrator\Fixtures\ClassWithStaticConstructor;
use EventSauce\ObjectHydrator\Fixtures\ClassWithUnmappedStringProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithUuidProperty;
use EventSauce\ObjectHydrator\Fixtures\TypeMapping\Animal;
use EventSauce\ObjectHydrator\Fixtures\TypeMapping\ClassThatMapsTypes;
use EventSauce\ObjectHydrator\Fixtures\TypeMapping\Frog;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithEnumListProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithEnumPropertyWithDefault;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithNullableEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithNullableUnitEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\CustomEnum;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class ObjectHydrationTestCase extends TestCase
{
    /**
     * @test
     */
    public function hydrating_a_polymorphic_property(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['child' => ['animal' => 'frog', 'color' => 'blue']];
        $object = $hydrator->hydrateObject(ClassThatMapsTypes::class, $payload);

        self::assertInstanceOf(ClassThatMapsTypes::class, $object);
        self::assertInstanceOf(Frog::class, $object->child);
    }
    /**
     * @test
     */
    public function hydrating_a_polymorphic_interface(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['nested' => ['muppet' => 'kermit', 'color' => 'blue']];
        $object = $hydrator->hydrateObject(Animal::class, $payload);

        self::assertInstanceOf(Animal::class, $object);
        self::assertInstanceOf(Frog::class, $object);
        self::assertEquals('blue', $object->color);
    }

    /**
     * @test
     */
    public function hydrating_with_class_level_map_from(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['nested' => ['name' => 'Frank']];
        $object = $hydrator->hydrateObject(ClassWithClassLevelMapFrom::class, $payload);

        self::assertInstanceOf(ClassWithClassLevelMapFrom::class, $object);
        self::assertEquals('Frank', $object->name);
    }

    /**
     * @test
     */
    public function hydrating_with_class_level_map_from_with_multiple_sources(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['first' => 1, 'second' => 2];
        $object = $hydrator->hydrateObject(ClassWithClassLevelMapFromMultiple::class, $payload);

        self::assertInstanceOf(ClassWithClassLevelMapFromMultiple::class, $object);
        self::assertEquals(1, $object->one);
        self::assertEquals(2, $object->two);
    }

    /**
     * @test
     */
    public function nullable_property_can_be_mapped_with_null_input(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithNullableInput::class, ['date' => null]);

        self::assertInstanceOf(ClassWithNullableInput::class, $object);
        self::assertNull($object->date);
    }

    /**
     * @test
     */
    public function class_with_parameter_with_default_value(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithDefaultValue::class, ['requiredValue' => 'supplied']);

        self::assertEquals('supplied', $object->requiredValue);
        self::assertEquals('default', $object->defaultValue);
    }

    /**
     * @test
     */
    public function nullable_property_can_be_mapped_with_real_input(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithNullableInput::class, ['date' => '2022-07-01']);

        self::assertInstanceOf(ClassWithNullableInput::class, $object);
        self::assertInstanceOf(\DateTimeImmutable::class, $object->date);
    }

    /**
     * @test
     */
    public function properties_can_be_mapped_from_a_specific_key(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithMappedStringProperty::class, ['my_name' => 'Frank']);

        self::assertInstanceOf(ClassWithMappedStringProperty::class, $object);
        self::assertEquals('Frank', $object->name);
    }

    /**
     * @test
     */
    public function mapping_a_nested_key(): void
    {
        $hydrator = $this->createObjectHydrator();

        /** @var ClassWithPropertyMappedFromNestedKey $object */
        $object = $hydrator->hydrateObject(
            ClassWithPropertyMappedFromNestedKey::class,
            ['nested' => ['name' => 'Frank']]
        );

        self::assertInstanceOf(ClassWithPropertyMappedFromNestedKey::class, $object);
        self::assertEquals('Frank', $object->name);
    }

    /**
     * @test
     */
    public function trying_to_map_a_nested_key_from_shallow_input(): void
    {
        $hydrator = $this->createObjectHydrator();

        $this->expectExceptionObject(UnableToHydrateObject::dueToMissingFields(ClassWithPropertyMappedFromNestedKey::class, ['nested.name']));

        $hydrator->hydrateObject(ClassWithPropertyMappedFromNestedKey::class, ['nested' => 'Frank']);
    }

    /**
     * @test
     */
    public function mapping_to_a_list_of_objects(): void
    {
        $hydrator = $this->createObjectHydrator();
        $input = [['my_name' => 'Frank'], ['my_name' => 'Renske']];

        $objects = $hydrator->hydrateObjects(ClassWithMappedStringProperty::class, $input);

        self::assertContainsOnlyInstancesOf(ClassWithMappedStringProperty::class, $objects);
    }

    /**
     * @test
     */
    public function mapping_to_an_array_of_objects(): void
    {
        $hydrator = $this->createObjectHydrator();
        $input = [['my_name' => 'Frank'], ['my_name' => 'Renske']];

        $objects = $hydrator->hydrateObjects(ClassWithMappedStringProperty::class, $input)->toArray();

        self::assertIsArray($objects);
        self::assertCount(2, $objects);
        self::assertContainsOnlyInstancesOf(ClassWithMappedStringProperty::class, $objects);
    }

    /**
     * @test
     */
    public function properties_are_mapped_by_name_by_default(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithUnmappedStringProperty::class, ['name' => 'Frank']);

        self::assertInstanceOf(ClassWithUnmappedStringProperty::class, $object);
        self::assertEquals('Frank', $object->name);
    }

    /**
     * @test
     */
    public function properties_can_be_cast_to_a_different_type(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithPropertyCasting::class, ['age' => '1234']);

        self::assertInstanceOf(ClassWithPropertyCasting::class, $object);
        self::assertEquals(1234, $object->age);
    }

    /**
     * @test
     */
    public function list_type_properties_can_be_cast_to_a_different_type(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithPropertyThatUsesListCasting::class, ['ages' => ['1234', '2345']]);

        self::assertInstanceOf(ClassWithPropertyThatUsesListCasting::class, $object);
        self::assertEquals([1234, 2345], $object->ages);
    }

    /**
     * @test
     */
    public function list_values_can_be_cast_to_objects(): void
    {
        $expectedChildren = [
            new ClassWithUnmappedStringProperty('Frank'),
            new ClassWithUnmappedStringProperty('Renske'),
        ];
        $hydrator = $this->createObjectHydrator();

        $payload = [
            'children' => [
                ['name' => 'Frank'],
                ['name' => 'Renske'],
            ],
        ];

        $object = $hydrator->hydrateObject(ClassWithPropertyThatUsesListCastingToClasses::class, $payload);

        self::assertInstanceOf(ClassWithPropertyThatUsesListCastingToClasses::class, $object);
        self::assertEquals($expectedChildren, $object->children);
    }

    /**
     * @test
     */
    public function using_default_key_conversion_from_snake_case(): void
    {
        $hydrator = $this->createObjectHydrator(
            new DefinitionProvider(null, new KeyFormatterForSnakeCasing())
        );

        $object = $hydrator->hydrateObject(ClassWithCamelCaseProperty::class, ['snake_case' => 'camelCase']);

        self::assertInstanceOf(ClassWithCamelCaseProperty::class, $object);
        self::assertEquals('camelCase', $object->snakeCase);
    }

    /**
     * @test
     */
    public function objects_can_have_static_constructors(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithStaticConstructor::class, ['name' => 'Renske']);

        self::assertInstanceOf(ClassWithStaticConstructor::class, $object);
        self::assertEquals('Renske', $object->name);
    }

    /**
     * @test
     */
    public function properties_are_mapped_automatically(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassThatContainsAnotherClass::class, ['child' => ['name' => 'Frank']]);

        self::assertInstanceOf(ClassThatContainsAnotherClass::class, $object);
        self::assertEquals('Frank', $object->child->name);
    }

    /**
     * @test
     */
    public function hydrating_a_complex_object_that_uses_property_casting(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithComplexTypeThatIsMapped::class, ['child' => 'de Jonge']);

        self::assertInstanceOf(ClassWithComplexTypeThatIsMapped::class, $object);
        self::assertEquals('de Jonge', $object->child->name);
    }

    /**
     * @test
     */
    public function hydrating_a_class_with_a_formatted_date(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithFormattedDateTimeInput::class, ['date' => '24-11-1987']);

        self::assertInstanceOf(ClassWithFormattedDateTimeInput::class, $object);
        self::assertEquals('1987-11-24 00:00:00', $object->date->format('Y-m-d H:i:s'));
        self::assertEquals('Europe/Amsterdam', $object->date->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function hydrating_a_class_with_multiple_casters_without_options(): void
    {
        $hydrator = $this->createObjectHydrator();
        $payload = [
            'id' => '9f960d77-7c9b-4bfd-9fc4-62d141efc7e5',
            'name' => 'Joe',
        ];

        $object = $hydrator->hydrateObject(ClassThatUsesMutipleCastersWithoutOptions::class, $payload);

        self::assertInstanceOf(ClassThatUsesMutipleCastersWithoutOptions::class, $object);
        self::assertEquals('9f960d77-7c9b-4bfd-9fc4-62d141efc7e5', $object->id->toString());
        self::assertEquals('joe', $object->name);
    }

    /**
     * @test
     */
    public function hydrating_a_class_with_a_not_casted_date_input(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithNotCastedDateTimeInput::class, ['date' => '2022-01-01 12:00:00']);

        self::assertInstanceOf(ClassWithNotCastedDateTimeInput::class, $object);
        self::assertEquals('2022-01-01 12:00:00', $object->date->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function missing_properties_result_in_an_exception(): void
    {
        $hydrator = $this->createObjectHydrator();

        $this->expectExceptionObject(UnableToHydrateObject::dueToMissingFields(ClassWithUnmappedStringProperty::class, ['name']));

        $hydrator->hydrateObject(ClassWithUnmappedStringProperty::class, []);
    }

    /**
     * @test
     * @requires PHP >= 8.1
     */
    public function hydrating_an_object_with_an_enum(): void
    {
        $hydrator = $this->createObjectHydratorFor81();

        $object = $hydrator->hydrateObject(ClassWithEnumProperty::class, ['enum' => 'one']);

        self::assertEquals(CustomEnum::VALUE_ONE, $object->enum);
    }

    /**
     * @test
     * @requires PHP >= 8.1
     */
    public function hydrating_an_object_with_a_nullable_enum(): void
    {
        $hydrator = $this->createObjectHydratorFor81();

        $object = $hydrator->hydrateObject(ClassWithNullableUnitEnumProperty::class, ['enum' => null]);

        self::assertNull($object->enum);
    }

    /**
     * @test
     * @requires PHP >= 8.1
     */
    public function hydrating_an_object_with_a_default_enum_value(): void
    {
        $hydrator = $this->createObjectHydratorFor81();

        $object = $hydrator->hydrateObject(ClassWithEnumPropertyWithDefault::class, []);

        self::assertEquals(CustomEnum::VALUE_ONE, $object->enum);
    }

    /**
     * @test
     * @requires PHP >= 8.1
     */
    public function hydrating_an_object_with_a_nullable_backed_enum(): void
    {
        $hydrator = $this->createObjectHydratorFor81();

        $object = $hydrator->hydrateObject(ClassWithNullableEnumProperty::class, [
            'enum' => null,
            'enumFromEmptyString' => '',
        ]);

        self::assertNull($object->enum);
        self::assertNull($object->enumFromEmptyString);
    }

    /**
     * @test
     * @requires PHP >= 8.1
     */
    public function hydrating_an_object_with_an_list_of_enums(): void
    {
        $hydrator = $this->createObjectHydratorFor81();

        $object = $hydrator->hydrateObject(ClassWithEnumListProperty::class, ['enums' => ['one', 'two']]);

        self::assertEquals([CustomEnum::VALUE_ONE, CustomEnum::VALUE_TWO], $object->enums);
    }

    /**
     * @test
     */
    public function hydrating_classes_that_do_not_exist_cause_an_exception(): void
    {
        $hydrator = $this->createObjectHydrator();

        $this->expectException(UnableToHydrateObject::class);

        $hydrator->hydrateObject('ThisClass\\DoesNotExist', []);
    }

    /**
     * @test
     */
    public function hydrating_a_class_with_a_nullable_property_defaults_to_null(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithNullableProperty::class, []);

        self::assertInstanceOf(ClassWithNullableProperty::class, $object);
        self::assertNull($object->defaultsToNull);
    }

    /** @test */
    public function hydrating_a_class_with_a_default_value_providing_caster(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithDefaultValueProvidingCaster::class, []);

        self::assertInstanceOf(ClassWithDefaultValueProvidingCaster::class, $object);
        self::assertEquals('some_default_value', $object->valueProvidedFromCaster);
    }

    /**
     * @test
     */
    public function class_with_nullable_property_with_default_uses_default(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithPropertiesWithDefaultValues::class, []);

        self::assertInstanceOf(ClassWithPropertiesWithDefaultValues::class, $object);
        self::assertEquals('default_used', $object->nullableWithDefaultString);
        self::assertEquals('default_string', $object->notNullableWithDefaultString);
    }

    /**
     * @test
     */
    public function missing_a_nested_field(): void
    {
        $hydrator = $this->createObjectHydrator();
        $payload = ['child' => []];

        $this->expectExceptionObject(UnableToHydrateObject::dueToError(
            ClassThatContainsAnotherClass::class,
            UnableToHydrateObject::dueToMissingFields(ClassWithUnmappedStringProperty::class, ['name'], ['child']),
        ));

        $hydrator->hydrateObject(ClassThatContainsAnotherClass::class, $payload);
    }

    /**
     * @test
     */
    public function constructing_a_property_with_multiple_casters(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['child' => 12345];
        $object = $hydrator->hydrateObject(ClassThatHasMultipleCastersOnSingleProperty::class, $payload);

        self::assertInstanceOf(ClassThatHasMultipleCastersOnSingleProperty::class, $object);
        self::assertEquals('12345', $object->child->name);
    }

    /**
     * @test
     */
    public function mapping_multiple_keys_to_one_object(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['value' => 'dog', 'name' => 'Rover', 'age' => 2];
        $object = $hydrator->hydrateObject(ClassThatUsesClassWithMultipleProperties::class, $payload);

        self::assertInstanceOf(ClassThatUsesClassWithMultipleProperties::class, $object);
        self::assertEquals('dog', $object->value);
        self::assertEquals('Rover', $object->child->name);
        self::assertEquals(2, $object->child->age);
    }

    /**
     * @test
     */
    public function casting_a_property_to_a_uuid(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['id' => '9f960d77-7c9b-4bfd-9fc4-62d141efc7e5'];
        $object = $hydrator->hydrateObject(ClassWithUuidProperty::class, $payload);

        self::assertInstanceOf(ClassWithUuidProperty::class, $object);
        self::assertInstanceOf(UuidInterface::class, $object->id);
        self::assertTrue($object->id->equals(Uuid::fromString('9f960d77-7c9b-4bfd-9fc4-62d141efc7e5')));
    }

    /**
     * @test
     */
    public function mapping_multiple_keys_to_one_object_with_renames(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['name' => 'Rover', 'mapped_age' => 2];
        $object = $hydrator->hydrateObject(ClassThatRenamesInputForClassWithMultipleProperties::class, $payload);

        self::assertInstanceOf(ClassThatRenamesInputForClassWithMultipleProperties::class, $object);
        self::assertEquals('Rover', $object->child->name);
        self::assertEquals(2, $object->child->age);
    }

    /**
     * @test
     */
    public function using_the_same_hydrator_with_different_options(): void
    {
        $hydrator = $this->createObjectHydrator();
        $payload = [
            'first' => [
                ['snakeCase' => 'first'],
            ],
            'second' => [
                ['age' => '34'],
            ],
        ];

        $object = $hydrator->hydrateObject(ClassThatCastsListsToDifferentTypes::class, $payload);

        self::assertInstanceOf(ClassThatCastsListsToDifferentTypes::class, $object);
        self::assertContainsOnlyInstancesOf(ClassWithCamelCaseProperty::class, $object->first);
        self::assertContainsOnlyInstancesOf(ClassWithPropertyCasting::class, $object->second);
    }

    /**
     * @test
     */
    public function hydrating_a_class_with_use_function_statement(): void
    {
        $hydrator = $this->createObjectHydrator();
        $payload = [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ];

        $object = $hydrator->hydrateObject(ClassThatTriggersUseStatementLookup::class, $payload);

        self::assertInstanceOf(ClassThatTriggersUseStatementLookup::class, $object);
    }

    /**
     * @test
     */
    public function hydrating_a_class_with_valid_docblock_array_following_scalar(): void {
        $hydrator = $this->createObjectHydrator();
        $payload = [
            'test' => 'Brad',
            'test2' => ['Gianna', 'Kate'],
        ];

        $object = $hydrator->hydrateObject(ClassWithDocblockAndArrayFollowingScalar::class, $payload);

        self::assertInstanceOf(ClassWithDocblockAndArrayFollowingScalar::class, $object);
    }

    /**
     * @test
     * @see https://github.com/EventSaucePHP/ObjectHydrator/issues/56
     */
    public function hydrating_a_class_with_valid_docblock_array_different_formats(): void {
        $hydrator = $this->createObjectHydrator();
        $payload = [
            'test' => ['Brad', 'Jones'],
            'test2' => ['Buffy', 'Witt'],
            'test3' => ['Flying', 'Spaghetti', 'Monster'],
            'test4' => ['One' => 1, 'Two' => 2],
            'test5' => [0 => 'Zero', 'One' => 'One'],
            'test6' => [['defaultsToNull' => 'Array member that is cast to an object.']],
            'test7' => [[]],
            'test8' => [[]],
            'test9' => [[]],
            'test10' => [[]],
        ];

        $object = $hydrator->hydrateObject(ClassWithDocblockArrayVariants::class, $payload);

        foreach (['test6', 'test7', 'test8', 'test9', 'test10'] as $property) {
            self::assertContainsOnlyInstancesOf(ClassWithNullableProperty::class, $object->{$property});
        }
        self::assertInstanceOf(ClassWithDocblockArrayVariants::class, $object);
    }

    protected function createObjectHydratorFor81(): ObjectMapper
    {
        return $this->createObjectHydrator();
    }

    abstract protected function createObjectHydrator(DefinitionProvider $definitionProvider = null): ObjectMapper;
}
