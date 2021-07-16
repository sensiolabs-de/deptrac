<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DependencyEmitter;

use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\Dependency\Dependency;
use Qossmic\Deptrac\Dependency\Result;

class ClassSuperglobalDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'ClassSuperglobalDependencyEmitter';
    }

    public function applyDependencies(AstMap $astMap, Result $dependencyResult): void
    {
        foreach ($astMap->getAstClassReferences() as $classReference) {
            foreach ($classReference->getDependencies() as $dependency) {
                if (AstMap\AstDependency::SUPERGLOBAL_VARIABLE !== $dependency->getType()) {
                    continue;
                }
                $dependencyResult->addDependency(
                    new Dependency(
                        $classReference->getTokenName(),
                        $dependency->getTokenName(),
                        $dependency->getFileOccurrence()
                    )
                );
            }
        }
    }
}
