<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Collector;

use LogicException;
use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\AstRunner\AstMap\AstClassReference;

class DirectoryCollector extends RegexCollector implements CollectorInterface
{
    public function getType(): string
    {
        return 'directory';
    }

    public function satisfy(
        array $configuration,
        AstClassReference $astClassReference,
        AstMap $astMap,
        Registry $collectorRegistry
    ): bool {
        $fileReference = $astClassReference->getFileReference();

        return $fileReference && 1 === preg_match($this->getValidatedPattern($configuration), $fileReference->getFilepath());
    }

    protected function getPattern(array $configuration): string
    {
        if (!isset($configuration['regex']) || !is_string($configuration['regex'])) {
            throw new LogicException('DirectoryCollector needs the regex configuration.');
        }

        return '#'.$configuration['regex'].'#i';
    }
}
