<?php

namespace Tests\Qossmic\Deptrac;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Configuration\Configuration;
use Qossmic\Deptrac\ContainerBuilder;
use Qossmic\Deptrac\LayerAnalyzer;
use Tests\Qossmic\Deptrac\Fixtures\LayerAnalyzer\ClassBar;
use Tests\Qossmic\Deptrac\Fixtures\LayerAnalyzer\ClassFoo;

/**
 * @covers \Qossmic\Deptrac\LayerAnalyzer
 */
class LayerAnalyzerTest extends TestCase
{
    public function testAnalyze(): void
    {
        $configuration = Configuration::fromArray([
            'paths' => [__DIR__.'/Fixtures/LayerAnalyzer/'],
            'layers' => [
                [
                    'name' => 'LayerFoo',
                    'collectors' => [
                        [
                            'type' => 'className',
                            'regex' => '.*Class.*',
                        ],
                    ],
                ],
            ],
            'ruleset' => [],
        ]);

        $container = (new ContainerBuilder(__DIR__))->build();
        $analyzer = $container->get(LayerAnalyzer::class);
        $classLikes = $analyzer->analyze($configuration, 'LayerFoo');

        self::assertSame(
            [
                ClassBar::class,
                ClassFoo::class,
            ],
            $classLikes
        );
    }
}
