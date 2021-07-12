<?php

declare(strict_types=1);

namespace SensioLabs\Deptrac\Collector;

use SensioLabs\Deptrac\AstRunner\AstMap;
use SensioLabs\Deptrac\AstRunner\AstMap\AstClassReference;

final class SuperGlobalsCollector implements CollectorInterface
{
    public function getType(): string
    {
        return 'superGlobals';
    }

    public function satisfy(array $configuration, AstClassReference $astClassReference, AstMap $astMap, Registry $collectorRegistry): bool
    {
        $fileReference = $astClassReference->getFileReference();
        $source = file_get_contents($fileReference->getFilepath());

        return strpos($source, '_GET') !== false;
    }
}
