<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Configuration;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Configuration\Loader;
use Qossmic\Deptrac\File\CouldNotReadFileException;

/**
 * @covers \Qossmic\Deptrac\Configuration\Loader
 */
final class LoaderTest extends TestCase
{
    private $loader;

    protected function setUp(): void
    {
        $this->loader = new Loader(new Loader\YmlFileLoader(), __DIR__);
    }

    public function testLoadReturnsConfiguration(): void
    {
        $file = __DIR__.'/Fixtures/depfile.yaml';

        $configuration = $this->loader->load($file);

        self::assertSame(
            [__DIR__.'/examples/Uncovered/'],
            $configuration->getPaths()
        );
        self::assertSame(
            [
                'currentWorkingDirectory' => __DIR__,
                'depfileDirectory' => __DIR__.'/Fixtures',
            ],
            $configuration->getParameters()
        );
    }

    public function testLoadReturnsConfigurationWithRelativePathOption(): void
    {
        $file = __DIR__.'/Fixtures/depfile-relative.yaml';

        $configuration = $this->loader->load($file);

        self::assertSame(
            [__DIR__.'/Fixtures/examples/Uncovered/'],
            $configuration->getPaths()
        );
    }

    public function testLoadThrowsImportFileCannotBeReadException(): void
    {
        $file = __DIR__.'/Fixtures/non-existent-baseline.yml';

        $this->expectException(CouldNotReadFileException::class);
        $this->expectExceptionMessageMatches('~non-existent-file\.yaml~');

        $this->loader->load($file);
    }

    public function testLoadWithBaseline(): void
    {
        $file = __DIR__.'/Fixtures/baseline-main.yml';

        $configuration = $this->loader->load($file);

        self::assertSame(
            [
                'DummyClass' => [
                    'FooClass',
                    'BarClass',
                ],
            ],
            $configuration->getRuleset()->getSkipViolations()->all()
        );
    }

    public function testLoadWithImports(): void
    {
        $file = __DIR__.'/Fixtures/imports.yaml';

        $configuration = $this->loader->load($file);

        self::assertCount(1, $configuration->getLayers());
        self::assertSame([__DIR__.'/Fixtures/examples/Uncovered/'], $configuration->getPaths());
    }
}
