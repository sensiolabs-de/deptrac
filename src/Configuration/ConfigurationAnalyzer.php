<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Configuration;

use Qossmic\Deptrac\Configuration\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConfigurationAnalyzer
{
    private const RECOGNIZED_TOKENS = [
        self::CLASS_TOKEN,
        self::CLASS_SUPERGLOBAL_TOKEN,
        self::USE_TOKEN,
        self::FILE_TOKEN,
        self::FUNCTION_TOKEN,
        self::FUNCTION_SUPERGLOBAL_TOKEN,
    ];

    public const CLASS_TOKEN = 'class';
    public const CLASS_SUPERGLOBAL_TOKEN = 'class_superglobal';
    public const USE_TOKEN = 'use';
    public const FILE_TOKEN = 'file';
    public const FUNCTION_TOKEN = 'function';
    public const FUNCTION_SUPERGLOBAL_TOKEN = 'function_superglobal';

    /** @var array{types: string[]} */
    private array $config;

    /**
     * @param array<string, mixed> $args
     *
     * @throws InvalidConfigurationException
     */
    public static function fromArray(array $args): self
    {
        /** @var array{count_use_statements: bool, types: string[]} $options */
        $options = (new OptionsResolver())
            ->setDefault('count_use_statements', true)
            ->setDefault('types', [self::CLASS_TOKEN])
            ->addAllowedTypes('count_use_statements', 'bool')
            ->addAllowedTypes('types', 'array')
            ->resolve($args);

        return new self($options);
    }

    /**
     * @param array{count_use_statements: bool, types: string[]} $config
     */
    private function __construct(array $config)
    {
        if ($config['count_use_statements']) {
            $config['types'][] = self::USE_TOKEN;
        }
        unset($config['count_use_statements']);

        foreach ($config['types'] as $type) {
            if (!in_array($type, self::RECOGNIZED_TOKENS, true)) {
                throw new \InvalidArgumentException('Unsupported analyzer type: '.$type);
            }
        }
        $this->config = $config;
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return $this->config['types'];
    }
}
