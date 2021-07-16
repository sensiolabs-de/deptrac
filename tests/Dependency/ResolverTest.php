<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Dependency;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\Configuration\ConfigurationAnalyzer;
use Qossmic\Deptrac\Dependency\Event\PostEmitEvent;
use Qossmic\Deptrac\Dependency\Event\PostFlattenEvent;
use Qossmic\Deptrac\Dependency\Event\PreEmitEvent;
use Qossmic\Deptrac\Dependency\Event\PreFlattenEvent;
use Qossmic\Deptrac\Dependency\InheritanceFlatter;
use Qossmic\Deptrac\Dependency\Resolver;
use Qossmic\Deptrac\DependencyEmitter\ClassDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\ClassSuperglobalDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\FileDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\FunctionDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\FunctionSuperglobalDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\UsesDependencyEmitter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $astMap = new AstMap([]);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->withConsecutive(
            [new PreEmitEvent('emitter')],
            [new PostEmitEvent()],
            [new PreEmitEvent('emitter6')],
            [new PostEmitEvent()],
            [new PreFlattenEvent()],
            [new PostFlattenEvent()]
        );

        $inheritanceFlatter = $this->createMock(InheritanceFlatter::class);
        $inheritanceFlatter->expects(self::once())->method('flattenDependencies');

        $emitter = $this->createMock(ClassDependencyEmitter::class);
        $emitter->method('getName')->willReturn('emitter');
        $emitter->expects(self::once())->method('applyDependencies');

        $emitter2 = $this->createMock(ClassSuperglobalDependencyEmitter::class);
        $emitter2->method('getName')->willReturn('emitter2');
        $emitter2->expects(self::never())->method('applyDependencies');

        $emitter3 = $this->createMock(FileDependencyEmitter::class);
        $emitter3->method('getName')->willReturn('emitter3');
        $emitter3->expects(self::never())->method('applyDependencies');

        $emitter4 = $this->createMock(FunctionDependencyEmitter::class);
        $emitter4->method('getName')->willReturn('emitter4');
        $emitter4->expects(self::never())->method('applyDependencies');

        $emitter5 = $this->createMock(FunctionSuperglobalDependencyEmitter::class);
        $emitter5->method('getName')->willReturn('emitter5');
        $emitter5->expects(self::never())->method('applyDependencies');

        $emitter6 = $this->createMock(UsesDependencyEmitter::class);
        $emitter6->method('getName')->willReturn('emitter6');
        $emitter6->expects(self::once())->method('applyDependencies');

        $resolver = new Resolver($dispatcher, $inheritanceFlatter, $emitter, $emitter2, $emitter3, $emitter4, $emitter5, $emitter6);

        $resolver->resolve($astMap, ConfigurationAnalyzer::fromArray([]));
    }
}
