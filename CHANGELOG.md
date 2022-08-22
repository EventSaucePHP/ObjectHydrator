# Changelog 

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.5.2] - 2022-08-22

### Fixed

- Ignore use function/const statements when resolving the use statement map (#25)

## [0.5.1] - 2022-08-09

### Fixed

- Handle omission of data for nullable parameters and parameters with a default

## [0.5.0] - 2022-08-08

### Added

- Expose missing fields in UnableToHydrate exception.
- Added information about which property caused hydration failure.

## [0.4.3] - 2022-07-08

### Fixed

- Prevent type-error when casting null values for nullable properties (#23)

## [0.4.2] - 2022-06-12

### Fixed

- Fixed phpstan template notations.

## [0.4.1] - 2022-06-08

### Added

- The `CastToDateTimeImmutable` caster now supports setting a timezone.

## [0.4.0] - 2022-06-05

### Deprecations

- The `ObjectHydrator` class was deprecated, use `ObjectMapperUsingReflection` and hint against the new `ObjectMapper` interface.
- The `ListOfObjects` class was deprecated, use the `IterableList` object instead.
- The `KeyFormattingWithoutConversion` class was deprecated, use the `KeyFormatterWithoutConversion` class instead.

### Breaking Changes

- The `ObjectHydrator` class was converted into an interface named `ObjectMapper`, the main implementation is `ObjectMapperUsingReflection`.
- The `PropertyDefintion` class was renamed to `PropertyHydrationDefinition` to be symmetrical with the new `PropertySerializationDefinition`.
- The `DefinitionProvider::provideDefinition` method was deprecated in favour of the new `provideHydrationDefinition` for symmetry with `provideSerializationDefinition`.
- The `KeyFormatter` interface was changed by adding a `keyToPropertyName` method, needed for serialization.
- The `ObjectHydrator::hydrateObjects` return type class was renamed from `ListOfObjects` to `IterableList`.

### Added

- The `ObjectMapper` interface was introduced, which represents both generated and reflection based mappers.
- [Major Feature] Serialization was added to the main `ObjectHydrator` interface.
- Array hydration now also has rudimentary support for docblock type hints (`@param Type[] $name`, `@param array<Type> $name`, `@param array<string, Type> $name`).

## [0.3.1] - 2022-05-27

### Fixed

- Allow same hydrator to be used with different constructor options.

## [0.3.0] - 2022-04-28

### Added

- Added ability to define nested input ([#12](https://github.com/EventSaucePHP/ObjectHydrator/pull/12))

## [0.2.0] - 2022-04-01

### Added

- Added `hydrateObjects`; a way to hydrate a list of objects in one go

## [0.1.0] - 2022-01-03

### Added

- Initial implementation of the object hydrator
