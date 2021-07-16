<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Configuration;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Configuration\ConfigurationAnalyzer;

/**
 * @covers \Qossmic\Deptrac\Configuration\ConfigurationAnalyzer
 */
final class ConfigurationAnalyzerTest extends TestCase
{
    public function testCounting(): void
    {
        $configuration = ConfigurationAnalyzer::fromArray([
            'count_use_statements' => true,
        ]);

        self::assertSame(['class', 'use'], $configuration->getTypes());
    }

    public function testNotCounting(): void
    {
        $configuration = ConfigurationAnalyzer::fromArray([
            'count_use_statements' => false,
        ]);

        self::assertSame(['class'], $configuration->getTypes());
    }

    public function testDefaultCounting(): void
    {
        $configuration = ConfigurationAnalyzer::fromArray([]);
        self::assertSame(['class', 'use'], $configuration->getTypes());
    }
}
