<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use function array_map;
use function array_pop;
use function count;
use function explode;
use function implode;
use function is_array;
use function str_replace;
use function var_dump;
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
        $accessorName = $definition->accessorName;
        $index = 0;
        $accessor = $definition->formattedAccessor();
        $key = $definition->payloadKey;
        $code = <<<CODE

        \$result['$key'] = \$object->$accessor;

CODE;
;

        $hasMultipleSerializers = count($definition->serializers) > 1;

        foreach ($definition->serializers as $valueType => [$serializer, $arguments]) {
            $index++;
            $serializerName = $accessorName . 'Serializer' . $index;
            $arguments = var_export($arguments, true);

            if ($hasMultipleSerializers) {
                $code .= <<<CODE
        if ( ! \$result['$key'] instanceof \\$valueType) {
            goto after_$serializerName;
        } 
CODE;
            }

            $code .= <<<CODE

        static \$$serializerName;
        
        if (\$$serializerName === null) {
            \$$serializerName = new \\$serializer(...$arguments);
        }
        
        \$result['$key'] = \${$serializerName}->serialize(\$result['$key'], \$this);
CODE;

            if ($hasMultipleSerializers) {
                $code .= <<<CODE

        after_$serializerName:
CODE;
            }
        }

        return $code;
    }
}
