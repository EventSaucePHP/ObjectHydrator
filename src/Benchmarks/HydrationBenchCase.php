<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Benchmarks;

use EventSauce\ObjectHydrator\Fixtures\ExampleData;
use EventSauce\ObjectHydrator\ObjectHydrator;
use Generator;
use League\ConstructFinder\ConstructFinder;
use ReflectionClass;

use function count;

abstract class HydrationBenchCase
{
    private ObjectHydrator $objectHydrator;

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

    /**
     * @ParamProviders({"provideSampleSizes"})
     * @BeforeMethods({"prepareHydrator"})
     */
    public function benchObjectHydration(): void
    {
        foreach ($this->examples as [$className, $data]) {
            $this->objectHydrator->hydrateObject($className, $data);
        }
    }

    public function prepareHydrator(array $params): void
    {
        $this->objectHydrator = $this->createObjectHydrator();
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

        yield ($exampleCount * 10) => [10];
        yield ($exampleCount * 75) => [75];
        yield ($exampleCount * 250) => [250];
        yield ($exampleCount * 500) => [500];
    }

    abstract protected function createObjectHydrator(): ObjectHydrator;
}
