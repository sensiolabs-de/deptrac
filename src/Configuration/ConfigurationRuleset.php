<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Configuration;

use InvalidArgumentException;

final class ConfigurationRuleset
{
    /** @var array<string, string[]> */
    private array $layerMap;

    private ConfigurationSkippedViolation $skipViolations;

    private bool $ignoreUncoveredInternalClasses;

    /**
     * @param array<string, string[]> $arr
     * @param ConfigurationSkippedViolation $skippedViolation
     * @param bool $ignoreUncoveredInternalClasses
     */
    public static function fromOptions(
        array $arr,
        ConfigurationSkippedViolation $skippedViolation,
        bool $ignoreUncoveredInternalClasses
    ): self {
        return new self($arr, $skippedViolation, $ignoreUncoveredInternalClasses);
    }

    /**
     * @param array<string, string[]> $layerMap
     * @param ConfigurationSkippedViolation $skippedViolation
     * @param bool $ignoreUncoveredInternalClasses
     */
    private function __construct(
        array $layerMap,
        ConfigurationSkippedViolation $skippedViolation,
        bool $ignoreUncoveredInternalClasses
    ) {
        $this->layerMap = $layerMap;
        $this->skipViolations = $skippedViolation;
        $this->ignoreUncoveredInternalClasses = $ignoreUncoveredInternalClasses;
    }

    /**
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    public function getAllowedDependencies(string $layerName): array
    {
        return array_values(array_unique($this->getTransitiveDependencies($layerName, [])));
    }

    public function getSkipViolations(): ConfigurationSkippedViolation
    {
        return $this->skipViolations;
    }

    public function ignoreUncoveredInternalClasses(): bool
    {
        return $this->ignoreUncoveredInternalClasses;
    }

    /**
     * @param string[] $previousLayers
     *
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    private function getTransitiveDependencies(string $layerName, array $previousLayers): array
    {
        if (in_array($layerName, $previousLayers, true)) {
            throw new InvalidArgumentException('Circular ruleset dependency for layer '.$layerName.' depending on: '.implode('->', $previousLayers));
        }
        $dependencies = [];
        foreach ($this->layerMap[$layerName] ?? [] as $layer) {
            if (0 === strncmp($layer, '+', 1)) {
                $layer = ltrim($layer, '+');
                $dependencies[] = $this->getTransitiveDependencies($layer, array_merge([$layerName], $previousLayers));
            }
            $dependencies[] = [$layer];
        }

        return [] === $dependencies ? [] : array_merge(...$dependencies);
    }
}
