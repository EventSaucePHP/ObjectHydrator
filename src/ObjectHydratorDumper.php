<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use function array_pop;
use function array_values;
use function count;
use function explode;
use function implode;
use function str_replace;
use function var_export;

final class ObjectHydratorDumper
{
    private HydrationDefinitionProvider $definitionProvider;

    public function __construct(HydrationDefinitionProvider $definitionProvider = null)
    {
        $this->definitionProvider = $definitionProvider ?? new ReflectionHydrationDefinitionProvider();
    }

    public function dump(array $classes, string $dumpedClassName): string
    {
        $parts = explode('\\', $dumpedClassName);
        $shortName = array_pop($parts);
        $namespace = implode('\\', $parts);
        $classes = ClassExpander::expandClassesForHydration($classes, $this->definitionProvider);
        $hydrators = [];
        $hydratorMap = [];

        foreach ($classes as $className) {
            $classDefinition = $this->definitionProvider->provideDefinition($className);
            $methodName = 'hydrate' . str_replace('\\', '', $className);
            $hydratorMap[] = "'$className' => \$this->$methodName(\$payload),";
            $hydrators[] = $this->dumpClassHydrator($className, $classDefinition);
        }

        $hydratorMapCode = implode("\n                ", $hydratorMap);
        $hydratorCode = implode("\n\n", $hydrators);

        return <<<CODE
<?php

declare(strict_types=1);

namespace $namespace;

use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\UnableToHydrateObject;

/**
 * @template T
 */
class $shortName extends ObjectHydrator
{
    public function __construct() {}

    /**
     * @param class-string<T> \$className
     * @return T
     */
    public function hydrateObject(string \$className, array \$payload): object
    {
        try {
            return match(\$className) {
                $hydratorMapCode
                default => throw new \\LogicException("No hydration defined for \$className"),
            };
        } catch (\\Throwable \$exception) {
            throw UnableToHydrateObject::dueToError(\$className, \$exception);
        }
    }
    
    $hydratorCode
}
CODE;
    }

    private function dumpClassHydrator(string $className, ClassHydrationDefinition $classDefinition)
    {
        $body = '';
        foreach ($classDefinition->propertyDefinitions as $definition) {
            $keys = $definition->keys;
            $property = $definition->property;

            if (count($keys) === 1) {
                $from = array_values($keys)[0];
                $from = implode('\'][\'', $from);
                $body .= <<<CODE

            \$value = \$payload['$from'] ?? null;

            if (\$value === null) {
                goto after_$property;
            }

CODE;
            } else {
                $collectKeys = '';

                foreach ($keys as $to => $from) {
                    $from = implode('\'][\'', $from);
                    $collectKeys .= <<<CODE

            \$to = \$payload['$from'] ?? null;

            if (\$to !== null) {
                \$value['$to'] = \$payload['$from'];
            }

CODE;
                }

                $body .= <<<CODE

            \$value = [];

            $collectKeys

            if (\$value === []) {
                goto after_$property;
            }

CODE;
            }

            foreach ($definition->propertyCasters as $index => [$caster, $options]) {
                ++$index;
                $casterOptions = var_export($options, true);
                $casterName = $property . 'Caster' . $index;

                if ($caster) {
                    $body .= <<<CODE

            static \$$casterName;

            if (\$$casterName === null) {
                \$$casterName = new \\$caster(...$casterOptions);
            }

            \$value = \${$casterName}->cast(\$value, \$this);

CODE;
                }
            }

            if ($definition->isEnum) {
                $body .= <<<CODE

            \$value = \\{$definition->concreteTypeName}::from(\$value);

CODE;
            } elseif ($definition->canBeHydrated) {
                $body .= <<<CODE

            if (is_array(\$value)) {
                \$value = \$this->hydrateObject('{$definition->concreteTypeName}', \$value);
            }

CODE;
            }

            $body .= <<<CODE

            \$properties['$property'] = \$value;

            after_$property:

CODE;
        }

        $methodName = 'hydrate' . str_replace('\\', '', $className);
        $constructionCode = $classDefinition->constructionStyle === 'new' ? "new \\$className(...\$properties)" : "\\$classDefinition->constructor(...\$properties)";

        return <<<CODE
        
        private function $methodName(array \$payload): \\$className
        {
            \$properties = []; 
            $body
            
            return $constructionCode;
        }
CODE;
    }
}
