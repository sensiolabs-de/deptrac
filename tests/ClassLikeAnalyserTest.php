<?php

namespace Tests\Qossmic\Deptrac;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\AstRunner\AstMap\ClassLikeName;
use Qossmic\Deptrac\ClassLikeAnalyzer;
use Qossmic\Deptrac\Configuration\Configuration;
use Qossmic\Deptrac\ContainerBuilder;
use Tests\Qossmic\Deptrac\Fixtures\ClassLikeAnalyzer\ClassFoo;

/**
 * @covers \Qossmic\Deptrac\ClassLikeAnalyzer
 */
class ClassLikeAnalyzerTest extends TestCase
{
    public function testAnalyze(): void
    {
        $configuration = Configuration::fromArray([
            'paths' => [__DIR__.'/Fixtures/ClassLikeAnalyzer/'],
            'layers' => [
                [
                    'name' => 'LayerFoo',
                    'collectors' => [
                        [
                            'type' => 'className',
                            'regex' => '.*ClassFoo',
                        ],
                    ],
                ],
            ],
            'ruleset' => [],
        ]);

        $container = (new ContainerBuilder(__DIR__))->build();
        $analyzer = $container->get(ClassLikeAnalyzer::class);
        $layers = $analyzer->analyze($configuration, ClassLikeName::fromFQCN(ClassFoo::class));

        self::assertSame(['LayerFoo'], $layers);
    }
}
