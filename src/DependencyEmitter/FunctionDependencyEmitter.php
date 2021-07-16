<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DependencyEmitter;

use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\Dependency\Dependency;
use Qossmic\Deptrac\Dependency\Result;

class FunctionDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'FunctionDependencyEmitter';
    }

    public function applyDependencies(AstMap $astMap, Result $dependencyResult): void
    {
        foreach ($astMap->getAstFileReferences() as $astFileReference) {
            foreach ($astFileReference->getFunctionReferences() as $astFunctionReference) {
                foreach ($astFunctionReference->getDependencies() as $dependency) {
                    if (AstMap\AstDependency::SUPERGLOBAL_VARIABLE === $dependency->getType()) {
                        continue;
                    }
                    $dependencyResult->addDependency(
                        new Dependency(
                            $astFunctionReference->getTokenName(),
                            $dependency->getTokenName(),
                            $dependency->getFileOccurrence()
                        )
                    );
                }
            }
        }
    }
}
