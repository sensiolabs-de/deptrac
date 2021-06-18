<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\AstRunner\Resolver;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\AstRunner\AstParser\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser\ParserFactory;
use Qossmic\Deptrac\AstRunner\Resolver\AnnotationDependencyResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeResolver;
use Qossmic\Deptrac\Configuration\ConfigurationAnalyzer;

final class AnnotationDependencyResolverTest extends TestCase
{
    public function testPropertyDependencyResolving(): void
    {
        $typeResolver = new TypeResolver();
        $parser = new NikicPhpParser(
            ParserFactory::createParser(),
            new AstFileReferenceInMemoryCache(),
            new TypeResolver(),
            new AnnotationDependencyResolver($typeResolver)
        );

        $filePath = __DIR__.'/Fixtures/AnnotationDependency.php';
        $astFileReference = $parser->parseFile($filePath, ConfigurationAnalyzer::fromArray([]));

        $astClassReferences = $astFileReference->getAstClassReferences();
        $annotationDependency = $astClassReferences[0]->getDependencies();

        self::assertCount(2, $astClassReferences);
        self::assertCount(7, $annotationDependency);
        self::assertCount(0, $astClassReferences[1]->getDependencies());

        self::assertSame(
            'Tests\Qossmic\Deptrac\Integration\Fixtures\AnnotationDependencyChild',
            $annotationDependency[0]->getClassLikeName()->toString()
        );
        self::assertSame($filePath, $annotationDependency[0]->getFileOccurrence()->getFilepath());
        self::assertSame(12, $annotationDependency[0]->getFileOccurrence()->getLine());
        self::assertSame('variable', $annotationDependency[0]->getType());

        self::assertSame(
            'Tests\Qossmic\Deptrac\Integration\Fixtures\AnnotationDependencyChild',
            $annotationDependency[1]->getClassLikeName()->toString()
        );
        self::assertSame($filePath, $annotationDependency[1]->getFileOccurrence()->getFilepath());
        self::assertSame(26, $annotationDependency[1]->getFileOccurrence()->getLine());
        self::assertSame('variable', $annotationDependency[1]->getType());

        self::assertSame(
            'Tests\Qossmic\Deptrac\Integration\Fixtures\AnnotationDependencyChild',
            $annotationDependency[2]->getClassLikeName()->toString()
        );
        self::assertSame($filePath, $annotationDependency[2]->getFileOccurrence()->getFilepath());
        self::assertSame(29, $annotationDependency[2]->getFileOccurrence()->getLine());
        self::assertSame('variable', $annotationDependency[2]->getType());

        self::assertSame(
            'Symfony\Component\Console\Exception\RuntimeException',
            $annotationDependency[3]->getClassLikeName()->toString()
        );
        self::assertSame($filePath, $annotationDependency[3]->getFileOccurrence()->getFilepath());
        self::assertSame(32, $annotationDependency[3]->getFileOccurrence()->getLine());
        self::assertSame('variable', $annotationDependency[3]->getType());

        self::assertSame(
            'Symfony\Component\Finder\SplFileInfo',
            $annotationDependency[4]->getClassLikeName()->toString()
        );
        self::assertSame($filePath, $annotationDependency[4]->getFileOccurrence()->getFilepath());
        self::assertSame(17, $annotationDependency[4]->getFileOccurrence()->getLine());
        self::assertSame('parameter', $annotationDependency[4]->getType());

        self::assertSame(
            'Tests\Qossmic\Deptrac\Integration\Fixtures\AnnotationDependencyChild',
            $annotationDependency[5]->getClassLikeName()->toString()
        );
        self::assertSame($filePath, $annotationDependency[5]->getFileOccurrence()->getFilepath());
        self::assertSame(17, $annotationDependency[5]->getFileOccurrence()->getLine());
        self::assertSame('returntype', $annotationDependency[5]->getType());

        self::assertTrue($astClassReferences[0]->isInternal());
        self::assertFalse($astClassReferences[1]->isInternal());
    }
}
