<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Throwable;

use function array_key_exists;
use function array_pop;
use function array_values;
use function explode;
use function implode;
use function in_array;
use function join;
use function var_export;

class DefinitionDumper
{
    private DefinitionProvider $definitionProvider;

    public function __construct(DefinitionProvider $definitionProvider = null)
    {
        $this->definitionProvider = $definitionProvider ?: new ReflectionDefinitionProvider();
    }

    /**
     * @throws Throwable
     */
    public function dump(array $classNames, string $dumpedClassName): string
    {
        $classNames = $this->expandClasses($classNames);
        $sections = [];
        foreach ($classNames as $className) {
            $definition = $this->definitionProvider->provideDefinition($className);
            $code = $this->dumpClassDefinition($definition);
            $sections[] = "            '$className' => $code";
        }

        $parts = explode('\\', $dumpedClassName);
        $shortName = array_pop($parts);
        $namespace = implode('\\', $parts);
        $sectionCode = implode(",\n", $sections);

        return <<<CODE
<?php

declare(strict_types=1);

namespace $namespace {

use EventSauce\ObjectHydrator\ClassDefinition;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\PropertyDefinition;
use LogicException;

class $shortName implements DefinitionProvider
{
    private array \$definitions;

    public function __construct()
    {
        \$this->definitions = [
            $sectionCode,
        ];
    }

    public function provideDefinition(string \$className): ClassDefinition
    {
        \$definition = \$this->definitions[\$className] ?? null;
        
        if (\$definition === null) {
            throw new LogicException('No definition found for class ' . \$className);
        }
        
        return \$definition;
    }
}

}
CODE;

    }

    private function dumpClassDefinition(ClassDefinition $definition): string
    {
        $constructor = $definition->constructor;
        $constructionStyle = $definition->constructionStyle;
        $properties = [];

        foreach ($definition->propertyDefinitions as $propertyDefinition) {
            $properties[] = $this->dumpPropertyDefinition($propertyDefinition);
        }

        $propertyCode = join(",\n", $properties);

        return <<<CODE
new ClassDefinition(
    '$constructor',
    '$constructionStyle',
    $propertyCode
            )
CODE;

    }

    private function dumpPropertyDefinition(PropertyDefinition $propertyDefinition): string
    {
        $propertyCasters = var_export($propertyDefinition->propertyCasters, true);
        $canBeHydrated = var_export($propertyDefinition->canBeHydrated, true);
        $isEnum = var_export($propertyDefinition->isEnum, true);
        $concreteTypeName = var_export($propertyDefinition->concreteTypeName, true);
        return <<<CODE
            new PropertyDefinition(
                '$propertyDefinition->key',
                '$propertyDefinition->property',
                $propertyCasters,
                $canBeHydrated,
                $isEnum,
                $concreteTypeName
            )
CODE;
    }

    private function expandClasses(array $classes): array
    {
        $classes = array_values($classes);

        for ($i = 0; array_key_exists($i, $classes); $i++) {
            $class = $classes[$i];
            $classDefinition = $this->definitionProvider->provideDefinition($class);

            foreach ($classDefinition->propertyDefinitions as $propertyDefinition) {
                if ($propertyDefinition->canBeHydrated === false) {
                    continue;
                }

                $className = (string) $propertyDefinition->concreteTypeName;

                if ( ! in_array($className, $classes)) {
                    $classes[] = $className;
                }
            }
        }

        return $classes;
    }
}
