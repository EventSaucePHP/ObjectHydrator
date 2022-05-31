# Object Hydrator

This is a utility that converts structured request data (for example: decoded JSON) into a
complex object structure. The intended use of this utility is to receive request data and
convert this into Command or Query object. The library is designed to follow a convention
and does __not__ validate input.

The object hydration can be achieved at **zero** expense, due to a ahead-of-time resolving of
hydration steps using an [optimized dumper](#maximizing-performance).

## Why does this library exist?

That's a good question, so let's dig in. The primary driver for creating this tool was the desire
to use objects (DTOs, Query and Command objects) to interact with a software model instead of
using plain (raw) data. The use of objects makes code easier to understand as it provides clarity
over what data is available, and what the data is intended for. The use of objects also prevents
having to check for the availability and correctness of the data at every place of use, it can be
checked once and trusted many times.

The use of objects also has down-sides, some more impactful than others. One of these is the
burden of converting data into objects, which is what this library aims to eliminate. By
providing a predictable convention much of the conversion can be automated away. The use of
instrumentation (in the form of property casters) expands these capabilities by allowing
users to provide re-usable building blocks that provide full control as to how properties are
converted from data to (complex) objects.

#### Quick links:

- [**Design goals**](#design-goals)
- [**Installation**](#installation)
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
    - [**Dumping an optimized hydrator**](#dumping-an-optimized-hydrator)

## Design goals

This package was created with a couple design goals in mind. They are the following:

- Object creation should not be _too_ magical (use no reflection for instantiation)
- There should not be a hard runtime requirement on reflection
- Constructed objects should be valid from construction
- Construction through (static) named constructors should be supported

## Installation

```bash
composer require eventsauce/object-hydrator
```

## Usage

By default, input is mapped by property name, and types need to match. By default, keys are mapped from snake_case input
to camelCase properties.

```php
use EventSauce\ObjectHydrator\ObjectHydrator;

$hydrator = new ObjectHydrator();

class ExampleCommand
{
    public function __construct(
        public readonly string $name,
        public readonly int $birthYear,
    ) {}
}

$command = $hydrator->hydrateObject(
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

$command = $hydrator->hydrateObject(
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

$hydrator->hydrateObject(ExampleCommand::class, [
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

$command = $hydrator->hydrateObject(
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

$command = $hydrator->hydrateObject(
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

$command = $hydrator->hydrateObject(
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

$command = $hydrator->hydrateObject(
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

$command = $hydrator->hydrateObject(
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

$command = $hydrator->hydrateObject(
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
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToMoney implements PropertyCaster
{
    public function __construct(
        private string $currency
    ) {}

    public function cast(mixed $value, ObjectHydrator $hydrator) : mixed
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

    public function cast(mixed $value, ObjectHydrator $hydrator) : mixed
    {
        assert(is_array($value));

        $type = $value['type'] ?? 'unknown';
        unset($value['type']);
        $className = $this->typeToClassMap[$type] ?? null;

        if ($className === null) {
            throw new LogicException("Unable to map type '$type' to class.");
        }

        return $hydrator->hydrateObject($className, $value);
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

## Maximizing performance

Reflection and dynamic code paths can be a performance "issue" in the hot-path. To remove the expense,
optimized version can be dumped. These dumps are generated PHP files that perform the same construction
of classes as the dynamic would, in an optimized way.

### Dumping an optimized hydrator

You can dump a fully optimized hydrator for a known set of classes. This dumper will dump the code required
for  constructing the entire object tree, it automatically resolves the nested classes it can hydrate.

The dumped code is **3-10x faster** than the reflection based implementation. 

```php
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\ObjectHydratorDumper;

$dumpedClassNamed = "AcmeCorp\\YourOptimizedHydrator";
$dumper = new ObjectHydratorDumper();
$classesToDump = [SomeCommand::class, AnotherCommand::class];

$code = $dumper->dump($classesToDump, $dumpedClassNamed);
file_put_contents('src/AcmeCorp/YourOptimizedHydrator.php', $code);

/** @var ObjectHydrator $hydrator */
$hydrator = new AcmeCorp\YourOptimizedHydrator();
$someObject = $hydrator->hydrateObject(SomeObject::class, $payload);
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
