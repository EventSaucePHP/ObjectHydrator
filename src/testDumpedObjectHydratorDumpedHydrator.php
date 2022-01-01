<?php

declare(strict_types=1);

namespace DumpedObjectHydrator;

use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\UnableToHydrateObject;

/**
 * @template T
 */
class DumpedHydrator extends ObjectHydrator
{
    /**
     * @param class-string<T> $className
     * @return T
     */
    public function hydrateObject(string $className, array $payload): object
    {
        try {
            return match($className) {
                'EventSauce\ObjectHydrator\Fixtures\CastToClassWithStaticConstructor' => $this->hydrateEventSauceObjectHydratorFixturesCastToClassWithStaticConstructor($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassThatContainsAnotherClass' => $this->hydrateEventSauceObjectHydratorFixturesClassThatContainsAnotherClass($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassThatHasMultipleCastersOnSingleProperty' => $this->hydrateEventSauceObjectHydratorFixturesClassThatHasMultipleCastersOnSingleProperty($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassThatImplementsAnInterface' => $this->hydrateEventSauceObjectHydratorFixturesClassThatImplementsAnInterface($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassThatRenamesInputForClassWithMultipleProperties' => $this->hydrateEventSauceObjectHydratorFixturesClassThatRenamesInputForClassWithMultipleProperties($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassThatUsesClassWithMultipleProperties' => $this->hydrateEventSauceObjectHydratorFixturesClassThatUsesClassWithMultipleProperties($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassWithComplexTypeThatIsMapped' => $this->hydrateEventSauceObjectHydratorFixturesClassWithComplexTypeThatIsMapped($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassWithFormattedDateTimeInput' => $this->hydrateEventSauceObjectHydratorFixturesClassWithFormattedDateTimeInput($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty' => $this->hydrateEventSauceObjectHydratorFixturesClassWithMappedStringProperty($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassWithMultipleProperties' => $this->hydrateEventSauceObjectHydratorFixturesClassWithMultipleProperties($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyCasting' => $this->hydrateEventSauceObjectHydratorFixturesClassWithPropertyCasting($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCasting' => $this->hydrateEventSauceObjectHydratorFixturesClassWithPropertyThatUsesListCasting($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCastingToClasses' => $this->hydrateEventSauceObjectHydratorFixturesClassWithPropertyThatUsesListCastingToClasses($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassWithStaticConstructor' => $this->hydrateEventSauceObjectHydratorFixturesClassWithStaticConstructor($payload),
                'EventSauce\ObjectHydrator\Fixtures\ClassWithUnmappedStringProperty' => $this->hydrateEventSauceObjectHydratorFixturesClassWithUnmappedStringProperty($payload),
                default => throw new \LogicException("No hydration defined for $className"),
            };
        } catch (\Throwable $exception) {
            throw UnableToHydrateObject::dueToError($className, $exception);
        }
    }
    
            
        private function hydrateEventSauceObjectHydratorFixturesCastToClassWithStaticConstructor(array $payload): \EventSauce\ObjectHydrator\Fixtures\CastToClassWithStaticConstructor
        {
            $properties = []; 
            
            
            return new \EventSauce\ObjectHydrator\Fixtures\CastToClassWithStaticConstructor(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassThatContainsAnotherClass(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassThatContainsAnotherClass
        {
            $properties = []; 
            
            $value = $payload['child'] ?? null;

            if ($value === null) {
                goto after_child;
            }
            if (is_array($value)) {
                $value = $this->hydrateObject('EventSauce\ObjectHydrator\Fixtures\ClassWithUnmappedStringProperty', $value);
            }
            $properties['child'] = $value;

            after_child:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassThatContainsAnotherClass(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassThatHasMultipleCastersOnSingleProperty(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassThatHasMultipleCastersOnSingleProperty
        {
            $properties = []; 
            
            $value = $payload['child'] ?? null;

            if ($value === null) {
                goto after_child;
            }
        global $childCaster0;

        if ($childCaster0 === null) {
            $childCaster0 = new \EventSauce\ObjectHydrator\PropertyCasters\CastToType(...array (
  0 => 'string',
));
        }

        $value = $childCaster0->cast($value, $this);        global $childCaster1;

        if ($childCaster1 === null) {
            $childCaster1 = new \EventSauce\ObjectHydrator\PropertyCasters\CastToArrayWithKey(...array (
  0 => 'name',
));
        }

        $value = $childCaster1->cast($value, $this);            if (is_array($value)) {
                $value = $this->hydrateObject('EventSauce\ObjectHydrator\Fixtures\ClassWithStaticConstructor', $value);
            }
            $properties['child'] = $value;

            after_child:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassThatHasMultipleCastersOnSingleProperty(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassThatImplementsAnInterface(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassThatImplementsAnInterface
        {
            $properties = []; 
            
            $value = $payload['name'] ?? null;

            if ($value === null) {
                goto after_name;
            }

            $properties['name'] = $value;

            after_name:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassThatImplementsAnInterface(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassThatRenamesInputForClassWithMultipleProperties(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassThatRenamesInputForClassWithMultipleProperties
        {
            $properties = []; 
            
            $value = [];

            
            if (array_key_exists('mapped_age', $payload)) {
                $value['age'] = $payload['mapped_age'];
            }
            if (array_key_exists('name', $payload)) {
                $value['name'] = $payload['name'];
            }

            if ($value === []) {
                goto after_child;
            }
            if (is_array($value)) {
                $value = $this->hydrateObject('EventSauce\ObjectHydrator\Fixtures\ClassWithMultipleProperties', $value);
            }
            $properties['child'] = $value;

            after_child:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassThatRenamesInputForClassWithMultipleProperties(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassThatUsesClassWithMultipleProperties(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassThatUsesClassWithMultipleProperties
        {
            $properties = []; 
            
            $value = $payload['value'] ?? null;

            if ($value === null) {
                goto after_value;
            }

            $properties['value'] = $value;

            after_value:

            $value = [];

            
            if (array_key_exists('age', $payload)) {
                $value['age'] = $payload['age'];
            }
            if (array_key_exists('name', $payload)) {
                $value['name'] = $payload['name'];
            }

            if ($value === []) {
                goto after_child;
            }
            if (is_array($value)) {
                $value = $this->hydrateObject('EventSauce\ObjectHydrator\Fixtures\ClassWithMultipleProperties', $value);
            }
            $properties['child'] = $value;

            after_child:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassThatUsesClassWithMultipleProperties(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassWithComplexTypeThatIsMapped(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassWithComplexTypeThatIsMapped
        {
            $properties = []; 
            
            $value = $payload['child'] ?? null;

            if ($value === null) {
                goto after_child;
            }
        global $childCaster0;

        if ($childCaster0 === null) {
            $childCaster0 = new \EventSauce\ObjectHydrator\Fixtures\CastToClassWithStaticConstructor(...array (
));
        }

        $value = $childCaster0->cast($value, $this);
            $properties['child'] = $value;

            after_child:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassWithComplexTypeThatIsMapped(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassWithFormattedDateTimeInput(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassWithFormattedDateTimeInput
        {
            $properties = []; 
            
            $value = $payload['date'] ?? null;

            if ($value === null) {
                goto after_date;
            }
        global $dateCaster0;

        if ($dateCaster0 === null) {
            $dateCaster0 = new \EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable(...array (
  0 => '!d-m-Y',
));
        }

        $value = $dateCaster0->cast($value, $this);
            $properties['date'] = $value;

            after_date:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassWithFormattedDateTimeInput(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassWithMappedStringProperty(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty
        {
            $properties = []; 
            
            $value = $payload['my_name'] ?? null;

            if ($value === null) {
                goto after_name;
            }

            $properties['name'] = $value;

            after_name:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassWithMultipleProperties(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassWithMultipleProperties
        {
            $properties = []; 
            
            $value = $payload['age'] ?? null;

            if ($value === null) {
                goto after_age;
            }

            $properties['age'] = $value;

            after_age:

            $value = $payload['name'] ?? null;

            if ($value === null) {
                goto after_name;
            }

            $properties['name'] = $value;

            after_name:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassWithMultipleProperties(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassWithPropertyCasting(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyCasting
        {
            $properties = []; 
            
            $value = $payload['age'] ?? null;

            if ($value === null) {
                goto after_age;
            }
        global $ageCaster0;

        if ($ageCaster0 === null) {
            $ageCaster0 = new \EventSauce\ObjectHydrator\PropertyCasters\CastToType(...array (
  0 => 'int',
));
        }

        $value = $ageCaster0->cast($value, $this);
            $properties['age'] = $value;

            after_age:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyCasting(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassWithPropertyThatUsesListCasting(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCasting
        {
            $properties = []; 
            
            $value = $payload['ages'] ?? null;

            if ($value === null) {
                goto after_ages;
            }
        global $agesCaster0;

        if ($agesCaster0 === null) {
            $agesCaster0 = new \EventSauce\ObjectHydrator\PropertyCasters\CastListToType(...array (
  0 => 'int',
));
        }

        $value = $agesCaster0->cast($value, $this);
            $properties['ages'] = $value;

            after_ages:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCasting(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassWithPropertyThatUsesListCastingToClasses(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCastingToClasses
        {
            $properties = []; 
            
            $value = $payload['children'] ?? null;

            if ($value === null) {
                goto after_children;
            }
        global $childrenCaster0;

        if ($childrenCaster0 === null) {
            $childrenCaster0 = new \EventSauce\ObjectHydrator\PropertyCasters\CastListToType(...array (
  0 => 'EventSauce\\ObjectHydrator\\Fixtures\\ClassWithUnmappedStringProperty',
));
        }

        $value = $childrenCaster0->cast($value, $this);
            $properties['children'] = $value;

            after_children:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCastingToClasses(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassWithStaticConstructor(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassWithStaticConstructor
        {
            $properties = []; 
            
            $value = $payload['name'] ?? null;

            if ($value === null) {
                goto after_name;
            }

            $properties['name'] = $value;

            after_name:

            
            return \EventSauce\ObjectHydrator\Fixtures\ClassWithStaticConstructor::buildMe(...$properties);
        }

        
        private function hydrateEventSauceObjectHydratorFixturesClassWithUnmappedStringProperty(array $payload): \EventSauce\ObjectHydrator\Fixtures\ClassWithUnmappedStringProperty
        {
            $properties = []; 
            
            $value = $payload['name'] ?? null;

            if ($value === null) {
                goto after_name;
            }

            $properties['name'] = $value;

            after_name:

            
            return new \EventSauce\ObjectHydrator\Fixtures\ClassWithUnmappedStringProperty(...$properties);
        }
}