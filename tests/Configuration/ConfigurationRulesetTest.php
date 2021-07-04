<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Configuration;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Configuration\ConfigurationRuleset;
use Qossmic\Deptrac\Configuration\ConfigurationSkippedViolation;

final class ConfigurationRulesetTest extends TestCase
{
    public function testFromArray(): void
    {
        $configurationRuleSet = ConfigurationRuleset::fromOptions(
            ['foo' => ['bar'], 'lala' => ['xx', 'yy']],
            ConfigurationSkippedViolation::fromArray([]),
            false
        );

        self::assertEquals(['bar'], $configurationRuleSet->getAllowedDependencies('foo'));
        self::assertEquals(['xx', 'yy'], $configurationRuleSet->getAllowedDependencies('lala'));
        self::assertEquals([], $configurationRuleSet->getAllowedDependencies('lalax'));
    }

    public function testFromArrayTransitive(): void
    {
        $configurationRuleSet = ConfigurationRuleset::fromOptions(
            [
                'foo' => ['+bar'],
                'bar' => ['baz'],
                'baz' => ['qux'],

                'qux' => ['+quuz', '+grault'],
                'quuz' => ['corge'],
                'grault' => ['+foo', 'baz'],

            ],
            ConfigurationSkippedViolation::fromArray([]),
            false
        );

        self::assertEquals(['baz', 'bar'], $configurationRuleSet->getAllowedDependencies('foo'));
        self::assertEquals(['corge', 'quuz', 'baz', 'bar', 'foo', 'grault'], $configurationRuleSet->getAllowedDependencies('qux'));
    }

    public function testFromArrayTransitiveCircular(): void
    {
        $configurationRuleSet = ConfigurationRuleset::fromOptions
        (
            [
                'a' => ['+b'],
                'b' => ['+c'],
                'c' => ['+a'],
            ],
            ConfigurationSkippedViolation::fromArray([]),
            false
        );
        $this->expectException(\InvalidArgumentException::class);
        $configurationRuleSet->getAllowedDependencies('a');
    }

    public function testSkipViolations(): void
    {
        $configuration = ConfigurationRuleset::fromOptions(
            [],
            ConfigurationSkippedViolation::fromArray(
                [
                    'FooClass' => [
                        'BarClass',
                        'AnotherClass',
                    ],
                ]),
            false
        );

        self::assertSame([
            'FooClass' => [
                'BarClass',
                'AnotherClass',
            ],
        ], $configuration->getSkipViolations()->all());
    }

    public function testIgnoreUncoveredInternalClassesSetToFalse(): void
    {
        $configuration = ConfigurationRuleset::fromOptions(
            [],
            ConfigurationSkippedViolation::fromArray([]),
            false
        );

        self::assertFalse($configuration->ignoreUncoveredInternalClasses());
    }

    public function testIgnoreUncoveredInternalClassesSetToTrue(): void
    {
        $configuration = ConfigurationRuleset::fromOptions([],
            ConfigurationSkippedViolation::fromArray([]),
            true
        );

        self::assertTrue($configuration->ignoreUncoveredInternalClasses());
    }
}
