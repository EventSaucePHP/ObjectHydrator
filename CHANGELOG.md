# Changelog 

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.8.0] 2026-01-13

### Fixed

- PHP 8.5 support
- Align serialize with hydrator picking up nested DTOs when #93 (by @WyriHaximus)

## [1.7.0] 2025-09-26

### Added

- Be able to filter out NULL values.
- Default caster for DateTimeZone

### Fixed

- Be able to assign more casters to promoted properties

## [1.6.1] 2024-12-15

### Fixes

- Fix PHP 8.4 deprecations

## [1.6.0] 2024-10-30

### Added

- Added interface for custom constructor resolution

### Fixes

- Skip parsing of closure use statements

## [1.5.0] 2024-08-17

### Improved

- Default to empty array in default serializer repository
- Allow skipping public method serialization
- Allow hydrating an array of enums.
- Serialize unit enums to their name.

## [1.4.0] 2023-08-03

### Fixed

- [Regression] Casters returning null causes problem for hydrator enums.

### Improved

- Naive property type resolving now handles a bunch more cases.

## [1.3.1] 2023-06-24

### Fixed

- Only try to resolve one property from the docblock to prevent parsing error ([#50](https://github.com/EventSaucePHP/ObjectHydrator/pull/50))

## [1.3.0] 2023-04-01

### Added

- This is not a joke release.
- `MapperSettings` can now also be specified using an interface.

## [1.2.0] 2023-03-11

### Added

- Added ability to define polymorhic object mapping (#29)


## [1.1.3] 2023-03-07

### Fixed

- Add mixed as native type in NaivePropertyTypeResolver (#40)

## [1.1.2] 2023-02-05

### Fixed

- Ensure generic template is of object type (#36)
- Prevent method name collisions in case SomeThing and Some\Thing both exist 

## [1.1.1] 2023-02-04

### Fixed

- Corrected @template generic usage for generated hydrator
- Prevented exception when scalar type is resolved from a docblock

## [1.1.0] - 2022-12-29

### Added

- Added ability to omit all public method from serialization.
- Added ability to omit all public properties from serialization.
- Added ability to omit specific public methods and properties from serialization.

## [1.0.0] - 2022-10-28

- Functionally equivalent to 0.5.5, stable release.

## [0.5.5] - 2022-09-15

- Support nullable enum properties #28

## [0.5.4] - 2022-09-13

### Fixed

- Fix typo in PHPDoc parser regex pattern #27

## [0.5.3] - 2022-09-09

### Fixed

- Fix cache keys colliding when using multiple casters without options #26

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
