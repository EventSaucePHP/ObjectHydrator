<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_pop;
use function array_values;
use function count;
use function explode;
use function implode;
use function str_replace;
use function var_export;

final class ObjectMapperCodeGenerator
{
    private DefinitionProvider $definitionProvider;

    public function __construct(DefinitionProvider $definitionProvider = null)
    {
        $this->definitionProvider = $definitionProvider ?? new DefinitionProvider();
    }

    public function dump(array $classes, string $dumpedClassName): string
    {
        $parts = explode('\\', $dumpedClassName);
        $shortName = array_pop($parts);
        $namespace = implode('\\', $parts);
        $hydrationClasses = ClassExpander::expandClassesForHydration($classes, $this->definitionProvider);
        $hydrators = [];
        $hydratorMap = [];

        foreach ($hydrationClasses as $className) {
            $classDefinition = $this->definitionProvider->provideHydrationDefinition($className);
            $methodName = 'hydrate' . str_replace('\\', '⚡️', $className);
            $hydratorMap[] = "'$className' => \$this->$methodName(\$payload),";
            $hydrators[] = $this->dumpClassHydrator($className, $classDefinition);
        }

        $hydratorMapCode = implode("\n                ", $hydratorMap);
        $hydratorCode = implode("\n\n", $hydrators);

        $serializationClasses = ClassExpander::expandClassesForSerialization($classes, $this->definitionProvider);
        $serializers = [];
        $serializationMap = [];
        $valueSerializers = $this->definitionProvider->allSerializers();

        foreach ($valueSerializers as $valueType => [$valueSerializerClass, $valueSerializerArgs]) {
            $serializers[] = $this->dumpValueTypeSerializer($valueType, $valueSerializerClass, $valueSerializerArgs);
            $methodName = 'serializeValue' . str_replace('\\', '⚡️', $valueType);
            $serializationMap[] = "'$valueType' => \$this->$methodName(\$object),";
        }

        foreach ($serializationClasses as $class) {
            if (array_key_exists($class, $valueSerializers)) {
                continue;
            }
            $definition = $this->definitionProvider->provideSerializationDefinition($class);
            $methodName = 'serializeObject' . str_replace('\\', '⚡️', $class);
            $serializationMap[] = "'$class' => \$this->$methodName(\$object),";
            $serializers[] = $this->dumpClassSerializer($class, $definition);
        }

        $serializationMapCode = implode("\n            ", $serializationMap);
        $serializationCode = implode("\n\n", $serializers);

        return <<<CODE
<?php

declare(strict_types=1);

namespace $namespace;

use EventSauce\ObjectHydrator\IterableList;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use EventSauce\ObjectHydrator\UnableToSerializeObject;
use Generator;

class $shortName implements ObjectMapper
{
    private array \$hydrationStack = [];
    public function __construct() {}

    /**
     * @template T of object
     * @param class-string<T> \$className
     * @return T
     */
    public function hydrateObject(string \$className, array \$payload): object
    {
        return match(\$className) {
            $hydratorMapCode
            default => throw UnableToHydrateObject::noHydrationDefined(\$className, \$this->hydrationStack),
        };
    }
    
    $hydratorCode
    
    private function serializeViaTypeMap(string \$accessor, object \$object, array \$payloadToTypeMap): array
    {
        foreach (\$payloadToTypeMap as \$payloadType => [\$valueType, \$method]) {
            if (is_a(\$object, \$valueType)) {
                return [\$accessor => \$payloadType] + \$this->{\$method}(\$object);
            }
        }

        throw new \LogicException('No type mapped for object of class: ' . get_class(\$object));
    }

    public function serializeObject(object \$object): mixed
    {
        return \$this->serializeObjectOfType(\$object, get_class(\$object));
    }

    /**
     * @template T
     *
     * @param T               \$object
     * @param class-string<T> \$className
     */
    public function serializeObjectOfType(object \$object, string \$className): mixed
    {
        try {
            return match(\$className) {
                $serializationMapCode
                default => throw new \\LogicException('No serialization defined for \$className'),
            };
        } catch (\\Throwable \$exception) {
            throw UnableToSerializeObject::dueToError(\$className, \$exception);
        }
    }
    
    $serializationCode
    
    

    /**
     * @template T
     *
     * @param class-string<T> \$className
     * @param iterable<array> \$payloads;
     *
     * @return IterableList<T>
     *
     * @throws UnableToHydrateObject
     */
    public function hydrateObjects(string \$className, iterable \$payloads): IterableList
    {
        return new IterableList(\$this->doHydrateObjects(\$className, \$payloads));
    }

    private function doHydrateObjects(string \$className, iterable \$payloads): Generator
    {
        foreach (\$payloads as \$index => \$payload) {
            yield \$index => \$this->hydrateObject(\$className, \$payload);
        }
    }

    /**
     * @template T
     *
     * @param class-string<T> \$className
     * @param iterable<array> \$payloads;
     *
     * @return IterableList<T>
     *
     * @throws UnableToSerializeObject
     */
    public function serializeObjects(iterable \$payloads): IterableList
    {
        return new IterableList(\$this->doSerializeObjects(\$payloads));
    }

    private function doSerializeObjects(iterable \$objects): Generator
    {
        foreach (\$objects as \$index => \$object) {
            yield \$index => \$this->serializeObject(\$object);
        }
    }
}
CODE;
    }

    private function dumpClassHydrator(string $className, ClassHydrationDefinition $classDefinition)
    {
        $body = '';

        if ($mapFrom = $classDefinition->mapFrom) {
            if (count($mapFrom) === 1) {
                $from = array_values($mapFrom)[0];
                $from = implode('\'][\'', $from);
                $body .= <<<CODE
            \$payload = \$payload['$from'] ?? null;
CODE;

            } else {
                $body .= <<<CODE
            \$payload = [

CODE;
                foreach ($mapFrom as $to => $from) {
                    $from = implode('\'][\'', $from);
                    $body .= <<<CODE
                '$to' => \$payload['$from'] ?? null,
CODE;

                }
                $body .= <<<CODE
            ];


CODE;

            }
        }

        foreach ($classDefinition->propertyDefinitions as $definition) {
            $keys = $definition->keys;
            $property = $definition->accessorName;

            if (count($keys) === 1) {
                $from = array_values($keys)[0];
                $isNullBody = <<<CODE
                goto after_$property;
CODE;

                if ($definition->nullable === false && ! $definition->hasDefaultValue) {
                    $fromConcatted = implode('.', $from);
                    $isNullBody = <<<CODE
                \$missingFields[] = '$fromConcatted';
                goto after_$property;
CODE;
                } elseif ($definition->nullable && ! $definition->hasDefaultValue) {
                    $isNullBody = <<<CODE
                \$properties['$property'] = null;
                goto after_$property;
CODE;

                }
                $from = implode('\'][\'', $from);
                $body .= <<<CODE

            \$value = \$payload['$from'] ?? null;
CODE;
            } else {
                $collectKeys = '';

                foreach ($keys as $to => $from) {
                    $from = implode('\'][\'', $from);
                    $collectKeys .= <<<CODE

            \$to = \$payload['$from'] ?? null;

            if (\$to !== null) {
                \$value['$to'] = \$to;
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

            foreach ($definition->casters as $index => [$caster, $options]) {
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

            if(isset($isNullBody)){
                $body .= <<<CODE
            if (\$value === null) {
                $isNullBody
            }
CODE;
            }



            if ($definition->isBackedEnum()) {
                $body .= <<<CODE

            \$value = \\{$definition->firstTypeName}::from(\$value);

CODE;
            } elseif ($definition->isEnum) {
                $body .= <<<CODE
            \$value = constant("$definition->firstTypeName::\$value");
CODE;
            } elseif ($definition->canBeHydrated) {
                if ($definition->propertyType->isCollection()) {
                    $body .= <<<CODE

            if (is_array(\$value[array_key_first(\$value)] ?? false)) {
                try {
                    \$this->hydrationStack[] = '$definition->accessorName';
                    \$value = \$this->hydrateObjects('{$definition->firstTypeName}', \$value)->toArray();
                } finally {
                    array_pop(\$this->hydrationStack);
                }
            }

CODE;
                } elseif ($definition->typeKey) {
                    $typeMatchMapCode = '';

                    foreach ($definition->typeMap as $payloadType => $valueType) {
                        $methodName = 'hydrate' . str_replace('\\', '⚡️', $valueType);
                        $typeMatchMapCode .= <<<CODE
                    '$payloadType' => \$this->$methodName(\$value),

CODE;
                    }

                    $body .= <<<CODE

            if (is_array(\$value)) {
                try {
                    \$this->hydrationStack[] = '$definition->accessorName';
                    \$valueType = \$value['$definition->typeKey'] ?? null;

                    if (\$valueType === null) {
                        throw new \LogicException('No type defined under key "$definition->typeKey"');
                    }

                    \$value = match (\$valueType) {
$typeMatchMapCode                            default => throw new \LogicException("No hydrator defined for \"\$valueType\"."),
                    };
                } finally {
                    array_pop(\$this->hydrationStack);
                }
            }

CODE;
                } else {
                    $methodName = 'hydrate' . str_replace('\\', '⚡️', $definition->firstTypeName);
                    $body .= <<<CODE

            if (is_array(\$value)) {
                try {
                    \$this->hydrationStack[] = '$definition->accessorName';
                    \$value = \$this->$methodName(\$value);
                } finally {
                    array_pop(\$this->hydrationStack);
                }
            }

CODE;
                }
            }

            $body .= <<<CODE

            \$properties['$property'] = \$value;

            after_$property:

CODE;
        }

        $methodName = 'hydrate' . str_replace('\\', '⚡️', $className);

        if ($classDefinition->canBeConstructed() === false) {
            if ($classDefinition->typeKey === null) {
                return <<<CODE

    private function $methodName(array \$payload): \\$className
    {
        throw UnableToHydrateObject::classIsNotInstantiable('$className', \$exception, stack: \$this->hydrationStack);
    }

CODE;

            }

            $typeMatchMapCode = '';

            foreach ($classDefinition->typeMap as $payloadType => $valueType) {
                $method = 'hydrate' . str_replace('\\', '⚡️', $valueType);
                $typeMatchMapCode .= <<<CODE
                 '$payloadType' => \$this->$method(\$payload),

CODE;
            }

            return <<<CODE
    private function $methodName(array \$payload): \\$className
    {
        try {{$body}
             \$type = \$payload['$classDefinition->typeKey'] ?? null;
        
             if (\$type === null) {
                throw UnableToHydrateObject::dueToMissingFields(\\$className::class, ['$classDefinition->typeKey'], stack: \$this->hydrationStack);
             }
             
             return match(\$type) {
$typeMatchMapCode                 default => throw new \LogicException("No hydrator defined for \"\$type\"."),
             };
        } catch (\\Throwable \$exception) {
            throw UnableToHydrateObject::dueToError('$className', \$exception, stack: \$this->hydrationStack);
        }
    }
CODE;

        }

        $constructionCode = $classDefinition->constructionStyle === 'new' ? "new \\$className(...\$properties)" : "\\$classDefinition->constructor(...\$properties)";

        return <<<CODE
        
    private function $methodName(array \$payload): \\$className
    {
        \$properties = []; 
        \$missingFields = [];
        try {{$body}
        } catch (\\Throwable \$exception) {
            throw UnableToHydrateObject::dueToError('$className', \$exception, stack: \$this->hydrationStack);
        }

        if (count(\$missingFields) > 0) {
            throw UnableToHydrateObject::dueToMissingFields(\\$className::class, \$missingFields, stack: \$this->hydrationStack);
        }

        try {
            return $constructionCode;
        } catch (\\Throwable \$exception) {
            throw UnableToHydrateObject::dueToError('$className', \$exception, stack: \$this->hydrationStack);
        }
    }
CODE;
    }

    private function dumpValueTypeSerializer(
        string $valueType,
        string $valueSerializerClass,
        array $valueSerializerArgs
    ): string {
        $methodName = 'serializeValue' . str_replace('\\', '⚡️', $valueType);
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

    private function dumpClassSerializer(string $class, ClassSerializationDefinition $definition): string
    {
        $methodName = 'serializeObject' . str_replace('\\', '⚡️', $class);
        $typeMapCode = '';
        $propertiesCode = '';

        if ($definition->typeKey) {
            $map = [];
            foreach ($definition->typeMap as $p => $v) {
                $map[$p] = [$v, 'serializeObject' . str_replace('\\', '⚡️', $v)];
            }
            $typeMapExported = var_export($map, true);
            $typeMapCode = <<<CODE
        
        \$result = \$this->serializeViaTypeMap('$definition->typeKey', \$object, $typeMapExported);

CODE;

        } else {
            $propertiesCode .= <<<CODE
        \$result = [];

CODE;

            $properties = array_map([$this, 'dumpClassProperty'], $definition->properties);
            $propertiesCode .= implode("\n        ", $properties);
        }

        $mapFrom = $this->dumpObjectMapFromSerializer($definition);

        return <<<CODE

    private function $methodName(mixed \$object): mixed
    {
        \\assert(\$object instanceof \\$class);
$typeMapCode$propertiesCode$mapFrom

        return \$result;
    }
CODE;
    }

    private function dumpClassProperty(PropertySerializationDefinition $definition): string
    {
        $propertyType = $definition->propertyType;
        $accessorName = $definition->accessorName;
        $accessor = $definition->formattedAccessor();
        $code = <<<CODE

        \$$definition->accessorName = \$object->$accessor;

CODE;

        if ($propertyType->allowsNull()) {
            $code .= <<<CODE

        if (\$$definition->accessorName === null) {
            goto after_$accessorName;
        }

CODE;
        }

        if ($definition->typeSpecifier) {
            $code .= $this->dumpMappedSerializer($definition);
        } elseif ( ! $definition->isComplexType()) {
            $code .= $this->dumpSimpleClassProperty($definition);
        } else {
            $code .= $this->dumpComplexClassProperty($definition);
        }

        $code .= <<<CODE
        after_$accessorName:
CODE;
        $code .= $this->dumpResultHydrator($definition);

        return $code;
    }

    private function dumpMappedSerializer(PropertySerializationDefinition $definition): string
    {
        $matchCode = '';

        foreach ($definition->typeMap as $payloadType => $valueType) {
            $methodName = 'serializeObject' . str_replace('\\', '⚡️', $valueType);
            $matchCode .= <<<CODE
            is_a(\$$definition->accessorName, '$valueType') => ['$definition->typeSpecifier' => '$payloadType'] + \$this->$methodName(\$$definition->accessorName),

CODE;

        }

        return <<<CODE
        \$$definition->accessorName = match (true) {
{$matchCode}            default => throw new \LogicException('No serializer specified for object of type ' . get_class(\$$definition->accessorName)),
        };

CODE;

    }

    /**
     * A simple class property is one with a single type, no union or intersection type.
     * The serialization of this type can be done in either of these 3 ways.
     *
     *    1. There is NO serializer defined and the type is built-in => no conversion
     *    2. There is NO serializer defined and the type is NOT built-in => serialize through ObjectMapper
     *    3. There IS a serializer defined => serialize through value serializer
     */
    private function dumpSimpleClassProperty(PropertySerializationDefinition $definition): string
    {
        $serializers = $definition->serializers;
        /** @var ConcreteType $firstType */
        $firstType = $definition->propertyType->concreteTypes()[0];

        if (count($serializers) === 0) {
            if ($firstType->isBuiltIn) {
                return '';
            }

            if ($firstType->isBackedEnum()) {
                return <<<CODE
        \$$definition->accessorName = \$$definition->accessorName->value;
CODE;
            }

            if ($firstType->isUnitEnum()) {
                return <<<CODE
        \$$definition->accessorName = \$$definition->accessorName->name;
CODE;
            }

            $prefix = 'serializeObject';

            if ($this->definitionProvider->provideSerializer($firstType->name) !== null) {
                $prefix = 'serializeValue';
            }

            $method = $prefix . str_replace('\\', '⚡️', $firstType->name);

            return <<<CODE
        \$$definition->accessorName = \$this->$method(\$$definition->accessorName);

CODE;
        }

        $accessorName = $definition->accessorName;
        $code = '';

        foreach ($serializers as $index => $serializer) {
            [$class, $arguments] = $serializer;
            $arguments = var_export($arguments, true);
            $serializerName = '$' . $accessorName . 'Serializer' . $index;

            $code .= <<<CODE
        static $serializerName;

        if ($serializerName === null) {
            $serializerName = new \\$class(...$arguments);
        }
        
        \$$definition->accessorName = {$serializerName}->serialize(\$$definition->accessorName, \$this);

CODE;
        }

        return $code;
    }

    /**
     * Serialization of a complex property is ... well, more complex. A complex type
     * contains any number of concrete types, either from a union or an intersection type.
     *
     * There are a couple of aspects that influence serialization. First off, a property serializer
     * may have been defined. In this case that serializer is always used. If no serializers are
     * defined, custom types are serialized through the ObjectMapper.
     */
    private function dumpComplexClassProperty(PropertySerializationDefinition $definition): string
    {
        $serializers = $definition->serializers;

        if (count($serializers) === 1 && array_keys($serializers)[0] === 0) {
            return $this->dumpSimpleClassProperty($definition);
        }

        $propertyType = $definition->propertyType;

        if (count($serializers) === 0 && ! $propertyType->containsBuiltInType()) {
            $matchStatement = '';

            foreach ($propertyType->concreteTypes() as $concreteType) {
                $serializerName = $this->definitionProvider->hasSerializerFor($concreteType->name)
                    ? 'serializeValue' . str_replace('\\', '⚡️', $concreteType->name)
                    : 'serializeObject' . str_replace('\\', '⚡️', $concreteType->name);
                $matchStatement .= <<<CODE
            '$concreteType->name' => \$this->$serializerName(\$$definition->accessorName),

CODE;
            }

            return <<<CODE
        \$$definition->accessorName = match(get_class(\$$definition->accessorName)) {
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
        if (is_object(\$$definition->accessorName)) {
            \$$definition->accessorName = \$this->serializeObject(\$$definition->accessorName);
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
        $code = <<<CODE
        if (\$$definition->accessorName instanceof \\$type) {

CODE;

        foreach ($definition->serializers[$type] as $i => $serializer) {
            [$class, $arguments] = $serializer;
            $serializerName = '$' . $definition->accessorName . 'Serializer' . $index . $i;
            $arguments = var_export($arguments, true);

            return <<<CODE
            static $serializerName;

            if ($serializerName === null) {
                $serializerName = new \\$class(...$arguments);
            }
            
            \$$definition->accessorName = {$serializerName}->serialize(\$$definition->accessorName, \$this);

CODE;
        }

        $code .= <<<CODE
        }

CODE;

        return $code;
    }

    private function dumpResultHydrator(PropertySerializationDefinition $definition): string
    {
        $tempVariable = '$' . $definition->accessorName;
        $keys = $definition->keys;

        if (count($keys) === 1) {
            $key = '[\'' . implode('\'][\'', array_pop($keys)) . '\']';

            return <<<CODE
        \$result$key = $tempVariable;

CODE;
        }

        $code = '';

        foreach ($keys as $tempKey => $resultKey) {
            $key = '[\'' . implode('\'][\'', $resultKey) . '\']';
            $code .= <<<CODE
        \$result$key = {$tempVariable}['$tempKey'];
CODE;
        }

        return $code;
    }

    private function dumpObjectMapFromSerializer(ClassSerializationDefinition $definition): string
    {
        $mapFrom = $definition->mapFrom;

        if ( ! $mapFrom) {
            return '';
        }

        $tempVariable = '$tmpResult';

        $code = <<<CODE
        $tempVariable = \$result;
        \$result = [];

CODE;

        if (count($mapFrom) === 1) {
            $key = '[\'' . implode('\'][\'', array_pop($mapFrom)) . '\']';

            return $code . <<<CODE
        \$result$key = $tempVariable;

CODE;
        }

        foreach ($mapFrom as $tempKey => $resultKey) {
            $key = '[\'' . implode('\'][\'', $resultKey) . '\']';
            $code .= <<<CODE
        \$result$key = {$tempVariable}['$tempKey'];
CODE;
        }

        return $code;
    }
}
