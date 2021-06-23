<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Collector;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\AstRunner\AstMap\AstClassReference;
use Qossmic\Deptrac\AstRunner\AstMap\ClassLikeName;
use Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\Collector\MethodCollector;
use Qossmic\Deptrac\Collector\Registry;

final class MethodCollectorTest extends TestCase
{
    public function testType(): void
    {
        self::assertSame('method', (new MethodCollector($this->createMock(NikicPhpParser::class)))->getType());
    }

    public function dataProviderSatisfy(): iterable
    {
        yield [
            ['name' => 'abc'],
            [
                $this->getClassMethod('abc'),
                $this->getClassMethod('abcdef'),
                $this->getClassMethod('xyz'),
            ],
            true,
        ];

        yield [
            ['name' => 'abc'],
            [
                $this->getClassMethod('abc'),
                $this->getClassMethod('xyz'),
            ],
            true,
        ];

        yield [
            ['name' => 'abc'],
            [
                $this->getClassMethod('xyz'),
            ],
            false,
        ];
    }

    /**
     * @dataProvider dataProviderSatisfy
     */
    public function testSatisfy(array $configuration, array $methods, bool $expected): void
    {
        $astClassReference = new AstClassReference(ClassLikeName::fromFQCN('foo'));

        $classLike = $this->createMock(Node\Stmt\ClassLike::class);
        $classLike->method('getMethods')->willReturn($methods);

        $parser = $this->createMock(NikicPhpParser::class);
        $parser
            ->method('getAstForClassReference')
            ->with($astClassReference)
            ->willReturn($classLike);

        $stat = (new MethodCollector($parser))->satisfy(
            $configuration,
            $astClassReference,
            $this->createMock(AstMap::class),
            $this->createMock(Registry::class)
        );

        self::assertSame($expected, $stat);
    }

    public function testClassLikeAstNotFoundDoesNotSatisfy(): void
    {
        $astClassReference = new AstClassReference(ClassLikeName::fromFQCN('foo'));
        $parser = $this->createMock(NikicPhpParser::class);
        $parser
            ->method('getAstForClassReference')
            ->with($astClassReference)
            ->willReturn(null);

        $satisfy = (new MethodCollector($parser))->satisfy(
            ['name' => 'abc'],
            $astClassReference,
            $this->createMock(AstMap::class),
            $this->createMock(Registry::class)
        );

        self::assertFalse($satisfy);
    }

    public function testMissingNameThrowsException(): void
    {
        $astClassReference = new AstClassReference(ClassLikeName::fromFQCN('foo'));
        $parser = $this->createMock(NikicPhpParser::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('MethodCollector needs the name configuration.');

        (new MethodCollector($parser))->satisfy(
            [],
            $astClassReference,
            $this->createMock(AstMap::class),
            $this->createMock(Registry::class)
        );
    }

    public function testInvalidRegexParam(): void
    {
        $astClassReference = new AstClassReference(ClassLikeName::fromFQCN('foo'));
        $parser = $this->createMock(NikicPhpParser::class);

        $this->expectException(\LogicException::class);

        (new MethodCollector($parser))->satisfy(
            ['name' => '/'],
            $astClassReference,
            $this->createMock(AstMap::class),
            $this->createMock(Registry::class)
        );
    }

    private function getClassMethod(string $name): \stdClass
    {
        $classMethod = new \stdClass();
        $classMethod->name = $name;

        return $classMethod;
    }
}
