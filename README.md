# Object Hydrator (and Serializer)

## Installation

```bash
composer require eventsauce/object-hydrator
```

## About

This library allows magic-less conversion from serialized data to object and back. Unlike other
object mappers, this library does not rely on magic reflection to set private properties. It
hydrates and serializes objects as if you would do it by hand. The hydration mechanism inspects
the constructor and figures out which keys need to map to which properties. The serialization
mechanism inspects all public properties and getter-methods, converts the values from objects
to plain data-structures. Unlike "magic" hydration mechanisms, that are able to grab
private properties, this way to map objects opens the door to object mapping without reflection.
You get all the convenience with none of the guilt (or performance hits).

This is a utility that converts structured request data (for example: decoded JSON) into a
complex object structure. The intended use of this utility is to receive request data and
convert this into Command or Query object. The library is designed to follow a convention
and does __not__ validate input.

## When and why would you use this?

That's a good question, so let's dig in. Initially, this library was created to map plain
data (like JSON request bodies) to strict object structures. The use of object (DTOs, Query
and Command objects) is a great way to create expressive code that is easy to understand. Objects
can be trusted to correctly represent concepts in your domain. The downside of using these
objects is that they can be tedious to use. Construction and serialization becomes repetitive
and writing the same code over and over is boring.  This library aims to remove the boring parts
of object hydration and serialization.

This library was built with two specific use-cases in mind:

1. Construction of DTOs, Query-object, and Command-objects.
2. Serialization and hydration of Event-objects.

Object hydration and serialization can be achieved at **zero** expense, due to an ahead-of-time
resolving steps using [code generation](#maximizing-performance).

#### Quick links:

- [**Installation**](#installation)
- [**About**](#about)
- [**Design goals**](#design-goals)
- [**Usage**](#usage)
    - [**Custom mapping key**](#custom-mapping-key)
    - [**Mapping from multiple keys**](#mapping-from-multiple-keys)
    - [**Property casting**](#property-casting)
    - [**Casting to scalar values**](#casting-to-scalar-values)
    - [**Casting to a list of scalar values**](#casting-to-a-list-of-scalar-values)
    - [**Casting to a list of objects**](#casting-to-a-list-of-objects)
    - [**Casting to DateTimeImmutable objects**](#casting-to-datetimeimmutable-objects)
    - [**Casting to Uuid objects (ramsey/uuid)**](#casting-to-uuid-objects-ramseyuuid)
    - [**Using multiple casters per property**](#creating-your-own-property-casters)
    - [**Creating your own property casters**](#creating-your-own-property-casters)
    - [**Static constructors**](#static-constructors)
- [**Maximizing performance**](#maximizing-performance)

## Design goals

This package was created with a couple design goals in mind. They are the following:

- Object creation should not be _too_ magical (use no reflection for instantiation)
- There should not be a hard runtime requirement on reflection
- Constructed objects should be valid from construction
- Construction through (static) named constructors should be supported


## Usage

This library supports hydration and serialization of objects.

- [**Hydration Usage**](#hydration-usage)
- [**Serilization Usage**](#serialization-usage)

## Hydration Usage

By default, input is mapped by property name, and types need to match. By default, keys are mapped from snake_case input
to camelCase properties.

```php
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;

$mapper = new ObjectMapperUsingReflection();

class ExampleCommand
{
    public function __construct(
        public readonly string $name,
        public readonly int $birthYear,
    ) {}
}

$command = $mapper->hydrateObject(
    ExampleCommand::class,
    [
        'name' => 'de Jonge',
        'birth_year' => 1987
    ],
);

$command->name === 'de Jonge';
$command->birthYear === 1987;
```

Complex objects are automagically resolved.

```php
class ChildObject
{
    public function __construct(
        public readonly string $value,
    ) {}
}

class ParentObject
{
    public function __construct(
        public readonly string $value,
        public readonly ChildObject $child,
    ) {}
}

$command = $mapper->hydrateObject(
    ParentObject::class,
    [
        'value' => 'parent value',
        'child' => [
            'value' => 'child value',
        ]
    ],
);
```

### Custom Mapping Key

```php
use EventSauce\ObjectHydrator\MapFrom;

class ExampleCommand
{
    public function __construct(
        public readonly string $name,
        #[MapFrom('year')]
        public readonly int $birthYear,
    ) {}
}
```

### Mapping from multiple keys

You can pass an array to capture input from multiple input keys. This is
useful when multiple values represent a singular code concept. The array
allows you to rename keys as well, further decoupling the input from the
constructed object graph.

```php
use EventSauce\ObjectHydrator\MapFrom;

class BirthDate
{
    public function __construct(
        public int $year,
        public int $month,
        public int $day
    ){}
}

class ExampleCommand
{
    public function __construct(
        public readonly string $name,
        #[MapFrom(['year_of_birth' => 'year', 'month', 'day'])]
        public readonly BirthDate $birthDate,
    ) {}
}

$mapper->hydrateObject(ExampleCommand::class, [
    'name' => 'Frank',
    'year_of_birth' => 1987,
    'month' => 11,
    'day' => 24,
]);
```

### Property casting

When the input type and property types are not compatible, values can be cast
to specific scalar types.

#### Casting to scalar values

```php
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;

class ExampleCommand
{
    public function __construct(
        #[CastToType('integer')]
        public readonly int $number,
    ) {}
}

$command = $mapper->hydrateObject(
    ExampleCommand::class,
    [
        'number' => '1234',
    ],
);
```

#### Casting to a list of scalar values

```php
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class ExampleCommand
{
    public function __construct(
        #[CastListToType('integer')]
        public readonly array $numbers,
    ) {}
}

$command = $mapper->hydrateObject(
    ExampleCommand::class,
    [
        'numbers' => ['1234', '2345'],
    ],
);
```

#### Casting to a list of objects

```php
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class Member
{
    public function __construct(
        public readonly string $name,
    ) {}
}

class ExampleCommand
{
    public function __construct(
        #[CastListToType(Member::class)]
        public readonly array $members,
    ) {}
}

$command = $mapper->hydrateObject(
    ExampleCommand::class,
    [
        'members' => [
            ['name' => 'Frank'],
            ['name' => 'Renske'],
        ],
    ],
);
```

#### Casting to DateTimeImmutable objects

```php
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;

class ExampleCommand
{
    public function __construct(
        #[CastToDateTimeImmutable('!Y-m-d')]
        public readonly DateTimeImmutable $birthDate,
    ) {}
}

$command = $mapper->hydrateObject(
    ExampleCommand::class,
    [
        'birthDate' => '1987-11-24',
    ],
);
```

#### Casting to Uuid objects (ramsey/uuid)

```php
use EventSauce\ObjectHydrator\PropertyCasters\CastToUuid;
use Ramsey\Uuid\UuidInterface;

class ExampleCommand
{
    public function __construct(
        #[CastToUuid]
        public readonly UuidInterface $id,
    ) {}
}

$command = $mapper->hydrateObject(
    ExampleCommand::class,
    [
        'id' => '9f960d77-7c9b-4bfd-9fc4-62d141efc7e5',
    ],
);
```

#### Using multiple casters per property

Create rich compositions of casting by using multiple casters.

```php
use EventSauce\ObjectHydrator\PropertyCasters\CastToArrayWithKey;
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;
use EventSauce\ObjectHydrator\MapFrom;
use Ramsey\Uuid\UuidInterface;

class ExampleCommand
{
    public function __construct(
        #[CastToType('string')]
        #[CastToArrayWithKey('nested')]
        #[MapFrom('number')]
        public readonly array $stringNumbers,
    ) {}
}

$command = $mapper->hydrateObject(
    ExampleCommand::class,
    [
        'number' => [1234],
    ],
);

$command->stringNumbers === ['nested' => [1234]];
```

### Creating your own property casters

You can create your own property caster to handle complex cases that  cannot follow the default conventions. Common cases
for casters are [union](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.union)
types or [intersection](https://wiki.php.net/rfc/pure-intersection-types) types.

Property casters give you full control over how a property is constructed. Property casters are attached to properties
using attributes, in fact, they _are_ attributes.

Let's look at an example of a property caster:

```php
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToMoney implements PropertyCaster
{
    public function __construct(
        private string $currency
    ) {}

    public function cast(mixed $value, ObjectMapper $mapper) : mixed
    {
        return new Money($value, Currency::fromString($this->currency));
    }
}

// ----------------------------------------------------------------------

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastUnionToType implements PropertyCaster
{
    public function __construct(
        private array $typeToClassMap
    ) {}

    public function cast(mixed $value, ObjectMapper $mapper) : mixed
    {
        assert(is_array($value));

        $type = $value['type'] ?? 'unknown';
        unset($value['type']);
        $className = $this->typeToClassMap[$type] ?? null;

        if ($className === null) {
            throw new LogicException("Unable to map type '$type' to class.");
        }

        return $mapper->hydrateObject($className, $value);
    }
}
```

You can now use these as attributes on the object you wish to hydrate:

```php
class ExampleCommand
{
    public function __construct(
        #[CastToMoney('EUR')]
        public readonly Money $money,
        #[CastUnionToType(['some' => SomeObject::class, 'other' => OtherObject::class])]
        public readonly SomeObject|OtherObject $money,
    ) {}
}
```

## Static constructors

Objects that require construction through static construction are supported. Mark the static method using
the `Constructor` attribute. In these cases, the attributes should be placed on the parameters of the
static constructor, not on `__construct`.

```php
use EventSauce\ObjectHydrator\Constructor;
use EventSauce\ObjectHydrator\MapFrom;

class ExampleCommand
{
    private function __construct(
        public readonly string $value,
    ) {}

    #[Constructor]
    public static function create(
        #[MapFrom('some_value')]
        string $value
    ): static {
        return new static($value);
    }
}
```

## Serialization Usage

By default, this library maps the public properties and getters to `snake_cased` arrays with plain data. 
When user-defined objects are encountered, these are automatically converted to the plain data counterpart.

```php
class ExampleCommand
{
    public function __construct(
        public readonly string $name,
        public readonly int $birthYear,
    ) {}
}


$command = new ExampleCommand('de Jonge', 1987);
$payload = $mapper->serializeObject($command);

$payload['name'] === 'de Jonge';
$payload['birth_year'] === 1987;
```

### Custom Key Mapping

Serialization inverts the key mapping used by hydration in a symmetrical way, including
the mapping from multiple keys.

```php
use EventSauce\ObjectHydrator\MapFrom;

class BirthDate
{
    public function __construct(
        public int $year,
        public int $month,
        public int $day
    ){}
}

class ExampleCommand
{
    public function __construct(
        #[MapFrom('my_name')]
        public readonly string $name,
        #[MapFrom(['year_of_birth' => 'year', 'month', 'day'])]
        public readonly BirthDate $birthDate,
    ) {}
}

$command = new ExampleCommand(
  'de Jonge',
  new BirthDate(1987, 11, 24)
);

$payload = $mapper->serializeObject($command);

$payload['my_name'] === 'de Jonge';
$payload['year_of_birth'] === 1987;
$payload['month'] === 11;
$payload['day'] === 24;
```

## Maximizing performance

Reflection and dynamic code paths can be a performance "issue" in the hot-path. To remove the expense,
optimized version can be dumped. These dumps are generated PHP files that perform the same construction
of classes as the dynamic would, in an optimized way.

You can dump a fully optimized hydrator for a known set of classes. This dumper will dump the code required
for  constructing the entire object tree, it automatically resolves the nested classes it can hydrate.

The dumped code is **3-10x faster** than the reflection based implementation. 

```php
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;

$dumpedClassNamed = "AcmeCorp\\YourOptimizedMapper";
$dumper = new ObjectMapperCodeGenerator();
$classesToDump = [SomeCommand::class, AnotherCommand::class];

$code = $dumper->dump($classesToDump, $dumpedClassNamed);
file_put_contents('src/AcmeCorp/YourOptimizedMapper.php', $code);

/** @var ObjectMapper $mapper */
$mapper = new AcmeCorp\YourOptimizedMapper();
$someObject = $mapper->hydrateObject(SomeObject::class, $payload);
```

### Tip: Use `league/construct-finder`

You can use the construct finder package from The PHP League to find all classes in
a given directory.

```bash
composer require league/construct-finder
```

```php
$classesToDump = ConstructFinder::locatedIn($directoryName)->findClassNames();
```


## Alternatives

This package is not unique, there are a couple implementations our there that do
the same, similar, or more than this package does.

- [cuyz/valinor](https://github.com/CuyZ/Valinor)
- [spatie/data-transfer-object](https://github.com/spatie/data-transfer-object)
- [jane-php/automapper](https://github.com/janephp/janephp/tree/next/src/Component/AutoMapper)
