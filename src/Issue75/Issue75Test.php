<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Issue75;

use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use League\ConstructFinder\ConstructFinder;
use PHPUnit\Framework\TestCase;

class Issue75Test extends TestCase
{
    /**
     * @test
     */
    public function recursing_with_hydration_and_serialization(): void
    {
        $classes = ConstructFinder::locatedIn(__DIR__)
            ->exclude('*Test.php')
            ->findAllNames();
        $dumper = new ObjectMapperCodeGenerator();
        $code = $dumper->dump($classes, Issue75Hydrator::class);
        \file_put_contents(__DIR__ . '/Issue75Hydrator.php', $code);

        $hydrator = new Issue75Hydrator();
        $instance = $hydrator->hydrateObject(TopLevel::class, $values = [
            'number' => 30,
            'lower' => [
                'amount' => [
                    'amount' => 100,
                ],
                'slot' => [
                    'name' => 'name',
                    'value' => 'value',
                ],
            ],
        ]);

        $serialized = $hydrator->serializeObject($instance);

        self::assertEquals($values, $serialized);
    }
}
