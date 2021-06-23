<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Collector;

abstract class RegexCollector
{
    /**
     * @param array<string, string|array> $configuration
     */
    abstract protected function getPattern(array $configuration): string;

    /**
     * @param array<string, string|array> $configuration
     */
    protected function getValidatedPattern(array $configuration): string
    {
        $pattern = $this->getPattern($configuration);
        set_error_handler(static function () {}, E_WARNING);
        $isRegularExpression = false !== preg_match($pattern, '');
        restore_error_handler();
        if ($isRegularExpression) {
            return $pattern;
        }
        throw new \LogicException('Invalid regex pattern '.$pattern);
    }
}
