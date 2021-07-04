<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Configuration;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Configuration\Configuration;
use Qossmic\Deptrac\Configuration\Exception;

/**
 * @covers \Qossmic\Deptrac\Configuration\Configuration
 */
final class ConfigurationTest extends TestCase
{
    public function testFromArrayRejectsLayersWithDuplicateNames(): void
    {
        $this->expectException(Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration can not contain multiple layers with the same name, got "baz", "foo" as duplicate.');

        Configuration::fromArray([
            'layers' => [
                [
                   'name' => 'foo',
                   'collectors' => [],
                ],
                [
                   'name' => 'foo',
                   'collectors' => [],
                ],
                [
                   'name' => 'bar',
                   'collectors' => [],
                ],
                [
                   'name' => 'baz',
                   'collectors' => [],
                ],
                [
                   'name' => 'baz',
                   'collectors' => [],
                ],
            ],
            'paths' => [
                'src',
            ],
            'ruleset' => [
                'foo' => [
                    'bar',
                ],
                'bar' => null,
                'baz' => [
                    'bar',
                ],
            ],
        ]);
    }

    public function testFromArrayRejectsRulesetUsingUnknownLayerNames(): void
    {
        $this->expectException(Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration can not reference rule sets with unknown layer names, got "quux", "qux" as unknown.');

        Configuration::fromArray([
            'layers' => [
                [
                   'name' => 'foo',
                   'collectors' => [],
                ],
                [
                   'name' => 'bar',
                   'collectors' => [],
                ],
                [
                   'name' => 'baz',
                   'collectors' => [],
                ],
            ],
            'paths' => [
                'src',
            ],
            'ruleset' => [
                'foo' => [
                    'bar',
                ],
                'bar' => null,
                'baz' => [
                    'bar',
                    'qux',
                ],
                'quux' => null,
            ],
        ]);
    }

    public function testFromArray(): void
    {
        $configuration = Configuration::fromArray([
            'layers' => [
                [
                   'name' => 'some_name',
                   'collectors' => [],
                ],
                [
                   'name' => 'xx',
                   'collectors' => [],
                ],
                [
                   'name' => 'yy',
                   'collectors' => [],
                ],
            ],
            'paths' => [
                'foo',
                'bar',
            ],
            'exclude_files' => [
                'foo2',
                'bar2',
            ],
            'ruleset' => [
                'some_name' => ['xx', 'yy'],
            ],
        ]);

        self::assertCount(3, $configuration->getLayers());
        self::assertEquals('some_name', $configuration->getLayers()[0]->getName());
        self::assertEquals(['foo', 'bar'], $configuration->getPaths());
        self::assertEquals(['foo2', 'bar2'], $configuration->getExcludeFiles());
        self::assertEquals(['xx', 'yy'], $configuration->getRuleset()->getAllowedDependencies('some_name'));
        self::assertTrue($configuration->getRuleset()->ignoreUncoveredInternalClasses());
    }

    public function testExcludedFilesAreOptional(): void
    {
        $configuration = Configuration::fromArray([
            'layers' => [
                [
                   'name' => 'some_name',
                   'collectors' => [],
                ],
                [
                   'name' => 'some_other_name',
                   'collectors' => [],
                ],
            ],
            'paths' => [
                'foo',
                'bar',
            ],
            'ruleset' => [
                'some_name' => ['some_other_name'],
            ],
        ]);

        self::assertSame([], $configuration->getExcludeFiles());
    }

    public function testFromWithNullExcludeFiles(): void
    {
        $configuration = Configuration::fromArray([
            'layers' => [],
            'paths' => [],
            'exclude_files' => null,
            'ruleset' => [],
        ]);

        self::assertEquals([], $configuration->getExcludeFiles());
    }
}
