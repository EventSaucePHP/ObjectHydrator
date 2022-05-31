<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Benchmarks;

use EventSauce\ObjectHydrator\Fixtures\ExampleData;
use EventSauce\ObjectHydrator\ObjectMapper;
use Generator;
use League\ConstructFinder\ConstructFinder;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use ReflectionClass;

use function count;
use function gc_collect_cycles;
use function gc_disable;
use function gc_enable;

abstract class HydrationBenchCase
{
    private ObjectMapper $objectHydrator;

    private array $examples = [];

    private array $exampleSeed = [];

    public function __construct()
    {
        $classes = ConstructFinder::locatedIn(__DIR__ . '/../Fixtures')->findClasses();

        foreach ($classes as $class) {
            $className = $class->name();
            $reflection = new ReflectionClass($className);
            $atttributes = $reflection->getAttributes(ExampleData::class);

            if (count($atttributes) === 0) {
                continue;
            }

            $exampleData = $atttributes[0]->newInstance()->payload;
            $examples[] = [$className, $exampleData];
        }

        $this->exampleSeed = $examples;
    }

    #[
        BeforeMethods(['prepareHydrator', 'disableGarbageCollector']),
        AfterMethods(['enableGarbageCollector']),
        ParamProviders('provideSampleSizes'),
        Warmup(3),
        Iterations(10),
        Revs(15)
    ]
    public function benchObjectHydration(): void
    {
        foreach ($this->examples as [$className, $data]) {
            $object = $this->objectHydrator->hydrateObject($className, $data);
            $this->objectHydrator->serializeObject($object);
        }
    }

    public function disableGarbageCollector(): void
    {
        gc_disable();
        gc_collect_cycles();
    }

    public function enableGarbageCollector(): void
    {
        gc_enable();
        gc_collect_cycles();
    }

    public function prepareHydrator(array $params): void
    {
        gc_disable();
        $this->objectHydrator = $this->createObjectMapper();
        $examples = [];
        [$scale] = $params;

        foreach ($this->exampleSeed as $example) {
            for ($i = 0; $i < $scale; ++$i) {
                $examples[] = $example;
            }
        }

        $this->examples = $examples;
    }

    public function provideSampleSizes(): Generator
    {
        $exampleCount = count($this->exampleSeed);

        yield ($exampleCount * 1) => [1];
        yield ($exampleCount * 10) => [10];
        yield ($exampleCount * 100) => [100];
        yield ($exampleCount * 250) => [250];
    }

    abstract protected function createObjectMapper(): ObjectMapper;
}
