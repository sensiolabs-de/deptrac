<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Configuration;

use Qossmic\Deptrac\Configuration\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Configuration
{
    /** @var ConfigurationLayer[] */
    private array $layers;
    /** @var string[] */
    private array $paths;
    /** @var string[] */
    private array $excludeFiles;
    private ConfigurationRuleset $ruleset;
    /** @var array<string, string> */
    private array $parameters;
    private ConfigurationAnalyzer $analyzer;
    /** @var array<string, array<string, mixed>> */
    private array $formatters;

    /**
     * @param array<string, mixed> $args
     *
     * @throws InvalidConfigurationException
     */
    public static function fromArray(array $args): self
    {
        $options = (new OptionsResolver())->setRequired([
            'layers',
            'paths',
            'ruleset',
        ])
        ->setDefault('parameters', [])
        ->setDefault('formatters', [])
        ->setDefault('exclude_files', [])
        ->setDefault('skip_violations', [])
        ->setDefault('analyzer', [])
        ->setDefault('ignore_uncovered_internal_classes', true)
        ->addAllowedTypes('parameters', 'array')
        ->addAllowedTypes('formatters', ['array', 'null'])
        ->addAllowedTypes('layers', 'array')
        ->addAllowedTypes('paths', 'array')
        ->addAllowedTypes('exclude_files', ['array', 'null'])
        ->addAllowedTypes('ruleset', 'array')
        ->addAllowedTypes('skip_violations', 'array')
        ->addAllowedTypes('analyzer', 'array')
        ->addAllowedTypes('ignore_uncovered_internal_classes', 'bool')
        ->resolve($args);

        return new self($options);
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function __construct(array $options)
    {
        $this->layers = array_map(static function (array $v): ConfigurationLayer {
            return ConfigurationLayer::fromArray($v);
        }, $options['layers']);

        $layerNames = array_values(array_map(static function (ConfigurationLayer $configurationLayer): string {
            return $configurationLayer->getName();
        }, $this->layers));

        $duplicateLayerNames = array_keys(array_filter(array_count_values($layerNames), static function (int $count): bool {
            return $count > 1;
        }));

        if ([] !== $duplicateLayerNames) {
            throw InvalidConfigurationException::fromDuplicateLayerNames(...$duplicateLayerNames);
        }

        /** @var list<string> $layerNamesUsedInRuleset */
        $layerNamesUsedInRuleset = array_unique(array_merge(
            array_keys($options['ruleset']),
            ...array_values(array_map(static function (?array $rules): array {
                return array_map(static fn (string $rule): string => ltrim($rule, '+'), (array) $rules);
            }, $options['ruleset']))
        ));

        $unknownLayerNames = array_diff(
            $layerNamesUsedInRuleset,
            $layerNames
        );

        if ([] !== $unknownLayerNames) {
            throw InvalidConfigurationException::fromUnknownLayerNames(...$unknownLayerNames);
        }

        $this->parameters = $options['parameters'];
        $this->ruleset = ConfigurationRuleset::fromOptions(
            $options['ruleset'],
            ConfigurationSkippedViolation::fromArray($options['skip_violations'] ?? []),
            (bool)($options['ignore_uncovered_internal_classes'] ?? false)
        );
        $this->paths = $options['paths'];
        $this->excludeFiles = (array) $options['exclude_files'];
        $this->formatters = (array) $options['formatters'];
        $this->analyzer = ConfigurationAnalyzer::fromArray($options['analyzer']);
    }

    /**
     * @return ConfigurationLayer[]
     */
    public function getLayers(): array
    {
        return $this->layers;
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @return string[]
     */
    public function getExcludeFiles(): array
    {
        return $this->excludeFiles;
    }

    public function getRuleset(): ConfigurationRuleset
    {
        return $this->ruleset;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormatterConfig(string $formatterName): array
    {
        return $this->formatters[$formatterName] ?? [];
    }

    public function getAnalyzer(): ConfigurationAnalyzer
    {
        return $this->analyzer;
    }
}
