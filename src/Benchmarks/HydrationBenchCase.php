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
        $classes = ConstructFinder::locatedIn(__DIR__ . '/../Fixtures')->findClasses();

        foreach ($classes as $class) {
            $className = $class->name();
            $reflection = new ReflectionClass($className);

            $atttributes = $reflection->getAttributes(ExampleData::class);

            if (count($atttributes) === 0) {
                continue;
            }

            $exampleData = $atttributes[0]->newInstance()->payload;

            for ($i = 0; $i < $scale; ++$i) {
                $examples[] = [$className, $exampleData];
            }
        }

        $this->examples = $examples;
    }

    public function provideSampleSizes(): Generator
    {
        yield [10];
        yield [50];
        yield [250];
    }

    abstract protected function createObjectHydrator(): ObjectHydrator;
}
