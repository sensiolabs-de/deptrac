<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DependencyEmitter;

use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\Dependency\Dependency;
use Qossmic\Deptrac\Dependency\Result;

/**
 * @deprecated
 */
class UsesDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'UsesDependencyEmitter';
    }

    public function applyDependencies(AstMap $astMap, Result $dependencyResult): void
    {
        foreach ($astMap->getAstFileReferences() as $fileReference) {
            $dependencies = $fileReference->getDependencies();
            foreach ($fileReference->getAstClassReferences() as $astClassReference) {
                foreach ($dependencies as $emittedDependency) {
                    if (AstMap\AstDependency::USE === $emittedDependency->getType()) {
                        $dependencyResult->addDependency(
                            new Dependency(
                                $astClassReference->getTokenName(),
                                $emittedDependency->getTokenName(),
                                $emittedDependency->getFileOccurrence()
                            )
                        );
                    }
                }
            }
        }
    }
}
