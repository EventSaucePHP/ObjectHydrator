<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\IntegrationTests;

use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\ObjectHydratorDumper;
use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\ObjectSerializerDumper;
use League\ConstructFinder\ConstructFinder;

use function array_push;
use function class_exists;
use function file_put_contents;
use function unlink;
use function version_compare;

use const PHP_VERSION;

class HydratingSerializedObjectsUsingCodeGenerationTest extends HydratingSerializedObjectsTestCase
{
    public function objectSerializer(): ObjectSerializer
    {
        $className = 'AcmeCorp\\GeneratedSerializer';

        if (class_exists($className)) {
            goto make_it;
        }

        $classes = $this->findClasses();
        $dumper = new ObjectSerializerDumper();
        $code = $dumper->dump($classes, $className);

        file_put_contents(__DIR__ . '/testSerializer.php', $code);
        include __DIR__ . '/testSerializer.php';
        unlink(__DIR__ . '/testSerializer.php');

        make_it:

        return new $className;
    }

    public function objectHydrator(): ObjectHydrator
    {
        $className = 'AcmeCorp\\GeneratedHydrator';

        if (class_exists($className)) {
            goto make_it;
        }

        $classes = $this->findClasses();
        $dumper = new ObjectHydratorDumper();
        $code = $dumper->dump($classes, $className);

        file_put_contents(__DIR__ . '/testHydrator.php', $code);
        include __DIR__ . '/testHydrator.php';
        unlink(__DIR__ . '/testHydrator.php');

        make_it:

        return new $className;
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
