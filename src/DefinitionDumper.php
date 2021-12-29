<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Throwable;

use function array_pop;
use function explode;
use function implode;
use function join;
use function var_export;

class DefinitionDumper
{
    private DefinitionProvider $provider;

    public function __construct(DefinitionProvider $provider = null)
    {
        $this->provider = $provider ?: new ReflectionDefinitionProvider();
    }

    /**
     * @throws Throwable
     */
    public function dump(array $classNames, string $dumpedClassName): string
    {
        $sections = [];
        foreach ($classNames as $className) {
            $definition = $this->provider->provideDefinition($className);
            $code = $this->dumpDefinition($definition);
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

    private function dumpDefinition(ClassDefinition $definition): string
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
        $propertyCaster = var_export($propertyDefinition->propertyCaster, true);
        $castingOptions = var_export($propertyDefinition->castingOptions, true);
        $canBeHydrated = var_export($propertyDefinition->canBeHydrated, true);
        $isEnum = var_export($propertyDefinition->isEnum, true);
        $concreteTypeName = var_export($propertyDefinition->concreteTypeName, true);
        return <<<CODE
            new PropertyDefinition(
                '$propertyDefinition->key',
                '$propertyDefinition->property',
                $propertyCaster,
                $castingOptions,
                $canBeHydrated,
                $isEnum,
                $concreteTypeName
            )
CODE;
    }
}
