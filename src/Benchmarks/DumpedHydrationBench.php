<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Benchmarks;

use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use League\ConstructFinder\ConstructFinder;
use function class_exists;
use function file_put_contents;
use function unlink;

class DumpedHydrationBench extends HydrationBenchCase
{
    protected function createObjectMapper(): ObjectMapper
    {
        if ( ! class_exists(DumpedObjectHydrator::class, true)) {
            $className = DumpedObjectHydrator::class;
            $classes = ConstructFinder::locatedIn(__DIR__ . '/../Fixtures')->findClassNames();
            $dumper = new ObjectMapperCodeGenerator();
            $code = $dumper->dump($classes, $className);
            file_put_contents(__DIR__ . '/DumpedObjectHydrator.php', $code);
            include __DIR__ . '/DumpedObjectHydrator.php';
            unlink(__DIR__ . '/DumpedObjectHydrator.php');
        }

        return new DumpedObjectHydrator();
    }
}
