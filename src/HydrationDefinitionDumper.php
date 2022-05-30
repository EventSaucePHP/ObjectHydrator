<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use Throwable;
use function array_pop;
use function explode;
use function implode;
use function var_export;

final class HydrationDefinitionDumper
{
    private HydrationDefinitionProvider $definitionProvider;

    public function __construct(HydrationDefinitionProvider $definitionProvider = null)
    {
        $this->definitionProvider = $definitionProvider ?: new HydrationDefinitionProviderUsingReflection();
    }

    /**
     * @throws Throwable
     */
    public function dump(array $classNames, string $dumpedClassName): string
    {
        $classNames = ClassExpander::expandClassesForHydration($classNames, $this->definitionProvider);
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

use EventSauce\ObjectHydrator\ClassHydrationDefinition;
use EventSauce\ObjectHydrator\ConcreteType;
use EventSauce\ObjectHydrator\HydrationDefinitionProvider;
use EventSauce\ObjectHydrator\PropertyHydrationDefinition;
use EventSauce\ObjectHydrator\PropertyType;
use LogicException;

class $shortName implements HydrationDefinitionProvider
{
    private array \$definitions;

    public function __construct()
    {
        \$this->definitions = [
            $sectionCode,
        ];
    }

    public function provideDefinition(string \$className): ClassHydrationDefinition
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

    private function dumpClassDefinition(ClassHydrationDefinition $definition): string
    {
        $constructor = $definition->constructor;
        $constructionStyle = $definition->constructionStyle;
        $properties = [];

        foreach ($definition->propertyDefinitions as $propertyDefinition) {
            $properties[] = $this->dumpPropertyDefinition($propertyDefinition);
        }

        $propertyCode = implode(",\n", $properties);

        return <<<CODE
new ClassHydrationDefinition(
    '$constructor',
    '$constructionStyle',
    $propertyCode
            )
CODE;
    }

    private function dumpPropertyDefinition(PropertyHydrationDefinition $propertyDefinition): string
    {
        $propertyType = $propertyDefinition->propertyType;
        $dumpedPropertyType = 'new PropertyType(' . ($propertyType->allowsNull() ? 'true' : 'false') . ',';

        foreach ($propertyType->concreteTypes() as $concreteType) {

            $dumpedPropertyType .= 'new ConcreteType(\''
                . $concreteType->name
                . '\', '
                . ($concreteType->isBuiltIn ? 'true' : 'false')
                . '),';
        }

        $dumpedPropertyType .= ')';

        $keys = var_export($propertyDefinition->keys, true);
        $propertyCasters = var_export($propertyDefinition->casters, true);
        $serialisers = var_export($propertyDefinition->serializers, true);
        $canBeHydrated = var_export($propertyDefinition->canBeHydrated, true);
        $isEnum = var_export($propertyDefinition->isEnum, true);
        $allowsNull = var_export($propertyDefinition->nullable, true);
        $concreteTypeName = var_export($propertyDefinition->firstTypeName, true);



        return <<<CODE
            new PropertyHydrationDefinition(
                $keys,
                '$propertyDefinition->accessorName',
                $propertyCasters,
                $serialisers,
                $dumpedPropertyType,
                $canBeHydrated,
                $isEnum,
                $allowsNull,
                $concreteTypeName
            )
CODE;
    }
}
