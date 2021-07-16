<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Dependency;

use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\Configuration\ConfigurationAnalyzer;
use Qossmic\Deptrac\Dependency\Event\PostEmitEvent;
use Qossmic\Deptrac\Dependency\Event\PostFlattenEvent;
use Qossmic\Deptrac\Dependency\Event\PreEmitEvent;
use Qossmic\Deptrac\Dependency\Event\PreFlattenEvent;
use Qossmic\Deptrac\DependencyEmitter\ClassDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\ClassSuperglobalDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\DependencyEmitterInterface;
use Qossmic\Deptrac\DependencyEmitter\FileDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\FunctionDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\FunctionSuperglobalDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\UsesDependencyEmitter;
use Qossmic\Deptrac\ShouldNotHappenException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Resolver
{
    private EventDispatcherInterface $dispatcher;
    private InheritanceFlatter $inheritanceFlatter;

    /** @var DependencyEmitterInterface[] */
    private array $emitters;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        InheritanceFlatter $inheritanceFlatter,
        ClassDependencyEmitter $classDependencyEmitter,
        ClassSuperglobalDependencyEmitter $classSuperglobalDependencyEmitter,
        FileDependencyEmitter $fileDependencyEmitter,
        FunctionDependencyEmitter $functionDependencyEmitter,
        FunctionSuperglobalDependencyEmitter $functionSuperglobalDependencyEmitter,
        UsesDependencyEmitter $usesDependencyEmitter
    ) {
        $this->dispatcher = $dispatcher;
        $this->inheritanceFlatter = $inheritanceFlatter;
        $this->emitters = [
            ConfigurationAnalyzer::CLASS_TOKEN => $classDependencyEmitter,
            ConfigurationAnalyzer::CLASS_SUPERGLOBAL_TOKEN => $classSuperglobalDependencyEmitter,
            ConfigurationAnalyzer::FILE_TOKEN => $fileDependencyEmitter,
            ConfigurationAnalyzer::FUNCTION_TOKEN => $functionDependencyEmitter,
            ConfigurationAnalyzer::FUNCTION_SUPERGLOBAL_TOKEN => $functionSuperglobalDependencyEmitter,
            ConfigurationAnalyzer::USE_TOKEN => $usesDependencyEmitter,
        ];
    }

    public function resolve(AstMap $astMap, ConfigurationAnalyzer $configurationAnalyzer): Result
    {
        $result = new Result();

        foreach ($configurationAnalyzer->getTypes() as $type) {
            if (!array_key_exists($type, $this->emitters)) {
                throw new ShouldNotHappenException();
            }

            $this->dispatcher->dispatch(new PreEmitEvent($this->emitters[$type]->getName()));
            $this->emitters[$type]->applyDependencies($astMap, $result);
            $this->dispatcher->dispatch(new PostEmitEvent());
        }

        $this->dispatcher->dispatch(new PreFlattenEvent());
        $this->inheritanceFlatter->flattenDependencies($astMap, $result);
        $this->dispatcher->dispatch(new PostFlattenEvent());

        return $result;
    }
}
