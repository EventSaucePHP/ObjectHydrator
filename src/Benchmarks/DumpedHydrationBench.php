<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Benchmarks;

use EventSauce\ObjectHydrator\ObjectHydrator;
use EventSauce\ObjectHydrator\ObjectHydratorDumper;
use League\ConstructFinder\Construct;
use League\ConstructFinder\ConstructFinder;

use function array_map;
use function class_exists;
use function file_put_contents;
use function unlink;

class DumpedHydrationBench extends HydrationBenchCase
{
    protected function createObjectHydrator(): ObjectHydrator
    {
        if ( ! class_exists(DumpedObjectHydrator::class, true)) {
            $className = DumpedObjectHydrator::class;
            $classes = ConstructFinder::locatedIn(__DIR__ . '/../Fixtures')->findClasses();
            $classes = array_map(fn(Construct $c) => $c->name(), $classes);
            $dumper = new ObjectHydratorDumper();
            $code = $dumper->dump($classes, $className);
            file_put_contents(__DIR__.'/DumpedObjectHydrator.php', $code);
            include __DIR__ . '/DumpedObjectHydrator.php';
            unlink(__DIR__ . '/DumpedObjectHydrator.php');
        }

        return new DumpedObjectHydrator();
    }
}
