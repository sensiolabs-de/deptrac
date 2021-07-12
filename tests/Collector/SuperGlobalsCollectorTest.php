<?php

declare(strict_types=1);

namespace Tests\SensioLabs\Deptrac\Collector;

use PHPUnit\Framework\TestCase;
use SensioLabs\Deptrac\AstRunner\AstMap;
use SensioLabs\Deptrac\Collector\DirectoryCollector;
use SensioLabs\Deptrac\Collector\Registry;
use SensioLabs\Deptrac\Collector\SuperGlobalsCollector;

final class SuperGlobalsCollectorTest extends TestCase
{
    public function dataProviderSatisfy(): iterable
    {
        yield [['regex' => 'foo/layer1/.*'], __DIR__.'/Fixtures/SuperGlobals_contained.php', true];
        yield [['regex' => 'foo/layer1/.*'], __DIR__.'/Fixtures/SuperGlobals_free.php', false];
    }

    /**
     * @dataProvider dataProviderSatisfy
     */
    public function testSatisfy(array $configuration, string $filePath, bool $expected): void
    {
        $fileReferenceBuilder = AstMap\FileReferenceBuilder::create($filePath);
        $fileReferenceBuilder->newClassLike('Test');
        $fileReference = $fileReferenceBuilder->build();

        $stat = (new SuperGlobalsCollector())->satisfy(
            $configuration,
            $fileReference->getAstClassReferences()[0],
            $this->createMock(AstMap::class),
            $this->createMock(Registry::class)
        );

        self::assertSame($expected, $stat);
    }
}
