# Object Hydrator

This is a utility that converts structured request data (for example: decoded JSON) into a
complex object structure. The intended use of this utility is to receive request data and
convert this into Command or Query object. The library is designed to follow a convention
and does __not__ validate input.

## Installation

```bash
composer require eventsauce/object-hydrator
```

## Usage

By default, input is mapped by property name, and types need to match.

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
        'birthYear' => 1987
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
        #[MapFrom('birth_year')]
        public readonly int $birthYear,
    ) {}
}
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

You can dump a fully optimized hydrator for a known set of classes. This dumper will dump the code required for
constructing the entire object tree, it automatically resolves the nested classes it can hydrate.

```php
use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\ObjectHydratorDumper;

$dumpedClassNamed = "AcmeCorp\\YourOptimizedHydrator";
$dumper = new ObjectHydratorDumper();
$classesToDump = [SomeCommand::class, AnotherCommand::class];

$code = $dumper->dump($classesToDump, $dumpedClassNamed);
file_put_contents('src/AcmeCorp/YourOptimizedHydrator.php');

/** @var ObjectHydrator $hydrator */
$hydrator = new AcmeCorp\YourOptimizedHydrator();
$someObject = $hydrator->hydrateObject(SomeObject::class, $payload);
```

### Dumping an optimized definition provider

When only need a cached version of the class and property definitions, you can dump those too.

```php
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\DefinitionDumper;

$dumpedClassNamed = "AcmeCorp\\YourOptimizedDefinitionProvider";
$dumper = new DefinitionDumper();
$classesToDump = [SomeCommand::class, AnotherCommand::class];

$code = $dumper->dump($classesToDump, $dumpedClassNamed);
file_put_contents('src/AcmeCorp/YourOptimizedDefinitionProvider.php');

/** @var DefinitionProvider $hydrator */
$hydrator = new AcmeCorp\YourOptimizedDefinitionProvider();
$definitionForSomeObject = $hydrator->provideDefinition(SomeObject::class);
```
