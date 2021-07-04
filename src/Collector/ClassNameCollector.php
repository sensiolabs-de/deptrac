<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Collector;

use LogicException;
use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\AstRunner\AstMap\AstClassReference;

class ClassNameCollector extends RegexCollector implements CollectorInterface
{
    public function getType(): string
    {
        return 'className';
    }

    public function satisfy(
        array $configuration,
        AstClassReference $astClassReference,
        AstMap $astMap,
        Registry $collectorRegistry
    ): bool {
        return $astClassReference->getClassLikeName()->match($this->getValidatedPattern($configuration));
    }

    protected function getPattern(array $configuration): string
    {
        if (!isset($configuration['regex']) || !is_string($configuration['regex'])) {
            throw new LogicException('ClassNameCollector needs the regex configuration.');
        }

        return '/'.$configuration['regex'].'/i';
    }
}
