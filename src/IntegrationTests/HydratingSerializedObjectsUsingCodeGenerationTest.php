<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use const PHP_VERSION;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use League\ConstructFinder\ConstructFinder;
use function array_push;
use function class_exists;
use function file_put_contents;
use function unlink;
use function version_compare;

class HydratingSerializedObjectsUsingCodeGenerationTest extends HydratingSerializedObjectsTestCase
{
    public function objectMapper(bool $serializeMapsAsObjects = false): ObjectMapper
    {
        $shortClassName = $serializeMapsAsObjects ? 'GeneratedObjectModeHydrator' : 'GeneratedArrayModeHydrator';
        $className = 'AcmeCorp\\' . $shortClassName;

        if (class_exists($className)) {
            goto make_it;
        }

        $classes = $this->findClasses();
        $dumper = new ObjectMapperCodeGenerator(serializeMapsAsObjects: $serializeMapsAsObjects);
        $code = $dumper->dump($classes, $className);

        $path = __DIR__ . '/' . $shortClassName . '.php';

        file_put_contents($path, $code);
        include_once $path;
        unlink($path);

        make_it:

        return new $className();
    }

    private function findClasses(): array
    {
        $classes = ConstructFinder::locatedIn(__DIR__ . '/../Fixtures')->findClassNames();

        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            array_push($classes, ...ConstructFinder::locatedIn(__DIR__ . '/../FixturesFor81')->findClassNames());
        }

        return $classes;
    }
}
