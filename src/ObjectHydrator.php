<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Throwable;

use function is_array;

/**
 * @template T
 * @template I
 */
class ObjectHydrator
{
    private ?DefinitionProvider $definitionProvider;

    /**
     * @var array<class-string<I>, I>
     */
    private $instances;

    public function __construct(
        ?DefinitionProvider $definitionProvider = null,
    ) {
        $this->definitionProvider = $definitionProvider ?: new ReflectionDefinitionProvider();
    }

    /**
     * @param class-string<T> $className
     * @return T
     */
    public function hydrateObject(string $className, array $payload): object
    {
        try {
            $classDefinition = $this->definitionProvider->provideDefinition($className);

            $properties = [];

            foreach ($classDefinition->propertyDefinitions as $definition) {
                $value = $payload[$definition->key] ?? null;

                if ($value === null) {
                    continue;
                }

                $property = $definition->property;

                if ($definition->propertyCaster) {
                    /** @var PropertyCaster $propertyCaster */
                    $propertyCaster = $this->instances[$definition->propertyCaster]
                        ??= new $definition->propertyCaster(...$definition->castingOptions);
                    $value = $propertyCaster->cast($value, $this,);
                }

                $typeName = $definition->concreteTypeName;

                if ($definition->isEnum) {
                    $value = $typeName::from($value);
                } elseif ($definition->canBeHydrated && is_array($value)) {
                    $value = $this->hydrateObject($typeName, $value);
                }

                $properties[$property] = $value;
            }

            return match ($classDefinition->constructionStyle) {
                'static' => ($classDefinition->constructor)(...$properties),
                'new' => new ($classDefinition->constructor)(...$properties),
            };
        } catch (Throwable $exception) {
            throw UnableToHydrateObject::dueToError($className, $exception);
        }
    }
}
