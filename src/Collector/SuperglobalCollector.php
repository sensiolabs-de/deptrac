<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Collector;

use LogicException;
use Qossmic\Deptrac\AstRunner\AstMap;

class SuperglobalCollector implements CollectorInterface
{
    public function getType(): string
    {
        return 'superglobal';
    }

    public function satisfy(
        array $configuration,
        AstMap\AstTokenReference $astTokenReference,
        AstMap $astMap,
        Registry $collectorRegistry
    ): bool {
        if (!$astTokenReference instanceof AstMap\AstVariableReference) {
            return false;
        }

        return in_array($astTokenReference->getTokenName()->toString(), $this->getNames($configuration), true);
    }

    /**
     * @param array<string, string|array> $configuration
     *
     * @return string[]
     */
    private function getNames(array $configuration): array
    {
        if (!isset($configuration['names']) || !is_array($configuration['names'])) {
            throw new LogicException('SuperglobalCollector needs the names configuration.');
        }

        return array_map(static fn ($_): string => (string) $_, $configuration['names']);
    }
}
