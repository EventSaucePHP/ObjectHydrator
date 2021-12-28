# Object Hydrator

This is a utility that converts structured request data (for example: decoded JSON) into a
complex object structure. The intended use of this utility is to receive request data and
convert this into Command or Query object. The library is designed to follow a convention
and does __not__ validate input.

## TODO

- [ ] Document property casters
- [ ] Implement reflectionless object hydrator

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

### Casting to scalar types

When the input type and property types are not compatible, values can be cast
to specific scalar types.

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
    ParentObject::class,
    [
        'number' => '1234',
    ],
);
```
