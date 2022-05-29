<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_pop;
use function count;
use function explode;
use function implode;
use function str_replace;
use function var_export;

final class ObjectSerializerDumper
{
    private SerializationDefinitionProviderUsingReflection $definitionProvider;

    public function __construct(
        SerializationDefinitionProviderUsingReflection $definitionProvider = null
    ) {
        $this->definitionProvider = $definitionProvider ?? new SerializationDefinitionProviderUsingReflection();
    }

    public function dump(array $classes, string $dumpedClassName): string
    {
        $classes = ClassExpander::expandClassesForSerialization($classes, $this->definitionProvider);
        $parts = explode('\\', $dumpedClassName);
        $shortName = array_pop($parts);
        $namespace = implode('\\', $parts);
        $serializers = [];
        $serializationMap = [];
        $valueSerializers = $this->definitionProvider->allSerializers();

        foreach ($valueSerializers as $valueType => [$valueSerializerClass, $valueSerializerArgs]) {
            $serializers[] = $this->dumpValueTypeSerializer($valueType, $valueSerializerClass, $valueSerializerArgs);
            $methodName = 'serializeValue' . str_replace('\\', '', $valueType);
            $serializationMap[] = "'$valueType' => \$this->$methodName(\$object),";
        }

        foreach ($classes as $class) {
            if (array_key_exists($class, $valueSerializers)) {
                continue;
            }
            $definition = $this->definitionProvider->provideDefinition($class);
            $methodName = 'serializeObject' . str_replace('\\', '', $class);
            $serializationMap[] = "'$class' => \$this->$methodName(\$object),";
            $serializers[] = $this->dumpClassDefinition($class, $definition);
        }

        $serializationMapCode = implode("\n                ", $serializationMap);
        $serializationCode = implode("\n\n", $serializers);

        return <<<CODE
<?php

declare(strict_types=1);

namespace $namespace;

use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\UnableToSerializeObject;

class $shortName implements ObjectSerializer
{
    public function serializeObject(object \$object): mixed
    {
        try {
            \$className = get_class(\$object);

            return match(\$className) {
                $serializationMapCode
                default => throw new \\LogicException('No serialization defined for \$className'),
            };
        } catch (\\Throwable \$exception) {
            throw UnableToSerializeObject::dueToError(\$className, \$exception);
        }
    }
    
    $serializationCode
}
CODE;
    }

    private function dumpValueTypeSerializer(
        string $valueType,
        string $valueSerializerClass,
        array $valueSerializerArgs
    ): string {
        $methodName = 'serializeValue' . str_replace('\\', '', $valueType);
        $serializerArgs = var_export($valueSerializerArgs, true);

        return <<<CODE

    private function $methodName(mixed \$value): mixed
    {
        static \$serializer;
        
        if (\$serializer === null) {
            \$serializer = new \\$valueSerializerClass(...$serializerArgs);
        }
        
        return \$serializer->serialize(\$value, \$this);
    }
CODE;
    }

    private function dumpClassDefinition(mixed $class, ClassSerializationDefinition $definition)
    {
        $methodName = 'serializeObject' . str_replace('\\', '', $class);
        $properties = array_map([$this, 'dumpClassProperty'], $definition->properties);
        $propertiesCode = implode("\n        ", $properties);

        return <<<CODE
    
    private function $methodName(mixed \$object): mixed
    {
        \\assert(\$object instanceof \\$class);
        \$result = [];
        $propertiesCode

        return \$result;
    }
CODE;
    }

    private function dumpClassProperty(PropertySerializationDefinition $definition): string
    {
        $propertyType = $definition->propertyType;
        $accessorName = $definition->accessorName;
        $accessor = $definition->formattedAccessor();
        $key = $definition->key;
        $code = <<<CODE

        \$result['$key'] = \$object->$accessor;

CODE;

        if ($propertyType->allowsNull()) {
            $code .= <<<CODE

        if (\$result['$key'] === null) {
            goto after_$accessorName;
        }

CODE;
        }

        if ( ! $definition->isComplexType()) {
            $code .= $this->dumpSimpleClassProperty($definition);
        } else {
            $code .= $this->dumpComplexClassProperty($definition);
        }

        $code .= <<<CODE
        after_$accessorName:
CODE;

        return $code;
    }

    /**
     * A simple class property is one with a single type, no union or intersection type.
     * The serialization of this type can be done in either of these 3 ways.
     *
     *    1. There is NO serializer defined and the type is built-in => no conversion
     *    2. There is NO serializer defined and the type is NOT built-in => serialize through ObjectSerializer
     *    3. There IS a serializer defined => serialize through value serializer
     */
    private function dumpSimpleClassProperty(PropertySerializationDefinition $definition): string
    {
        $key = $definition->key;
        $serializers = $definition->serializers;
        /** @var ConcreteType $firstType */
        $firstType = $definition->propertyType->concreteTypes()[0];

        if (count($serializers) === 0) {
            if ($firstType->isBuiltIn) {
                return '';
            }

            if ($firstType->isBackedEnum()) {
                return <<<CODE
        \$result['$key'] = \$result['$key']->value;
CODE;
            }

            if ($firstType->isUnitEnum()) {
                return <<<CODE
        \$result['$key'] = \$result['$key']->name;
CODE;
            }

            $prefix = 'serializeObject';

            if ($this->definitionProvider->provideSerializer($firstType->name) !== null) {
                $prefix = 'serializeValue';
            }

            $method = $prefix . str_replace('\\', '', $firstType->name);

            return <<<CODE
        \$result['$key'] = \$this->$method(\$result['$key']);

CODE;
        }

        $accessorName = $definition->accessorName;
        [$class, $arguments] = $serializers[0];
        $arguments = var_export($arguments, true);
        $serializerName = '$' . $accessorName . 'Serializer';

        return <<<CODE
        static $serializerName;
        
        if ($serializerName === null) {
            $serializerName = new \\$class(...$arguments);
        }
        
        \$result['$key'] = {$serializerName}->serialize(\$result['$key'], \$this);

CODE;
    }

    /**
     * Serialization of a complex property is ... well, more complex. A complex type
     * contains any number of concrete types, either from a union or an intersection type.
     *
     * There are a couple of aspects that influence serialization. First off, a property serializer
     * may have been defined. In this case that serializer is always used. If no serializers are
     * defined, custom types are serialized through the ObjectSerializer.
     */
    private function dumpComplexClassProperty(PropertySerializationDefinition $definition): string
    {
        $serializers = $definition->serializers;

        if (count($serializers) === 1 && array_keys($serializers)[0] === 0) {
            return $this->dumpSimpleClassProperty($definition);
        }

        $key = $definition->key;
        $propertyType = $definition->propertyType;

        if (count($serializers) === 0 && ! $propertyType->containsBuiltInType()) {
            $matchStatement = '';

            foreach ($propertyType->concreteTypes() as $concreteType) {
                $serializerName = $this->definitionProvider->hasSerializerFor($concreteType->name)
                    ? 'serializeValue' . str_replace('\\', '', $concreteType->name)
                    : 'serializeObject' . str_replace('\\', '', $concreteType->name);
                $matchStatement .= <<<CODE
            '$concreteType->name' => \$this->$serializerName(\$result['$key']),

CODE;

            }

            return <<<CODE
        \$result['$key'] = match(get_class(\$result['$key'])) {
            $matchStatement
        };

CODE;
        }

        if (count($serializers) === count($propertyType->concreteTypes())) {
            $code = '';
            $index = 0;

            foreach (array_keys($serializers) as $type) {
                ++$index;
                $code .= $this->renderPartial($type, $definition, $index);
            }

            return $code;
        }

        $index = 0;
        $code = '';

        foreach ($propertyType->concreteTypes() as $concreteType) {
            ++$index;
            $type = $concreteType->name;

            if (array_key_exists($type, $definition->serializers)) {
                $code .= $this->renderPartial($type, $definition, $index);
            }
        }

        if ( ! $propertyType->containsOnlyBuiltInTypes()) {
            $code .= <<<CODE
        if (is_object(\$result['$key'])) {
            \$result['$key'] = \$this->serializeObject(\$result['$key']);
        }

CODE;
        }

        return $code;
    }

    private function renderPartial(
        string $type,
        PropertySerializationDefinition $definition,
        int $index,
    ): string {
        $key = $definition->key;
        [$class, $arguments] = $definition->serializers[$type];
        $serializerName = '$' . $definition->accessorName . 'Serializer' . $index;
        $arguments = var_export($arguments, true);

        return <<<CODE
        if (\$result['$key'] instanceof \\$type) {
            static $serializerName;
            
            if ($serializerName === null) {
                $serializerName = new \\$class(...$arguments);
            }
            
            \$result['$key'] = {$serializerName}->serialize(\$result['$key'], \$this);
            goto after_$definition->accessorName;
        }

CODE;
    }
}
