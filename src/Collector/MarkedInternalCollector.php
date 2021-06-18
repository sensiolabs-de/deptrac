<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Collector;

use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\AstRunner\AstMap\AstClassReference;

class MarkedInternalCollector implements CollectorInterface
{
    public function getType(): string
    {
        return 'markedInternal';
    }

    public function satisfy(array $configuration, AstClassReference $astClassReference, AstMap $astMap, Registry $collectorRegistry): bool
    {
        return $astClassReference->isInternal();
    }
}
