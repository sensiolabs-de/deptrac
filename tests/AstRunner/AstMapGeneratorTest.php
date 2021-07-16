<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\AstRunner;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\AstRunner\AstMap\ClassLikeName;
use Qossmic\Deptrac\AstRunner\AstParser\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser\ParserFactory;
use Qossmic\Deptrac\AstRunner\AstRunner;
use Qossmic\Deptrac\AstRunner\Resolver\AnnotationDependencyResolver;
use Qossmic\Deptrac\AstRunner\Resolver\AnonymousClassResolver;
use Qossmic\Deptrac\AstRunner\Resolver\ClassConstantResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeResolver;
use Qossmic\Deptrac\Configuration\ConfigurationAnalyzer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyClassB;
use Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyClassC;
use Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyTraitA;
use Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyTraitB;
use Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyTraitC;
use Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyTraitClass;
use Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyTraitD;

final class AstMapGeneratorTest extends TestCase
{
    use ArrayAsserts;

    private function getAstMap(string $fixture): AstMap
    {
        $typeResolver = new TypeResolver();
        $astRunner = new AstRunner(
            new EventDispatcher(),
            new NikicPhpParser(
                ParserFactory::createParser(),
                new AstFileReferenceInMemoryCache(),
                $typeResolver,
                new AnnotationDependencyResolver($typeResolver),
                new AnonymousClassResolver(),
                new ClassConstantResolver()
            )
        );

        return $astRunner->createAstMapByFiles([$fixture], ConfigurationAnalyzer::fromArray([]));
    }

    public function testBasicDependencyClass(): void
    {
        $astMap = $this->getAstMap(__DIR__.'/Fixtures/BasicDependency/BasicDependencyClass.php');

        self::assertArrayValuesEquals(
            [
                'Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyClassA::9 (Extends)',
                'Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyClassInterfaceA::9 (Implements)',
            ],
            $this->getInheritsAsString($astMap->getClassReferenceByClassName(ClassLikeName::fromFQCN(BasicDependencyClassB::class)))
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyClassInterfaceA::13 (Implements)',
                'Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyClassInterfaceB::13 (Implements)',
            ],
            $this->getInheritsAsString($astMap->getClassReferenceByClassName(ClassLikeName::fromFQCN(BasicDependencyClassC::class)))
        );
    }

    public function testBasicTraitsClass(): void
    {
        $astMap = $this->getAstMap(__DIR__.'/Fixtures/BasicDependency/BasicDependencyTraits.php');

        self::assertArrayValuesEquals(
            [],
            $this->getInheritsAsString($astMap->getClassReferenceByClassName(ClassLikeName::fromFQCN(BasicDependencyTraitA::class)))
        );

        self::assertArrayValuesEquals(
            [],
            $this->getInheritsAsString($astMap->getClassReferenceByClassName(ClassLikeName::fromFQCN(BasicDependencyTraitB::class)))
        );

        self::assertArrayValuesEquals(
            ['Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyTraitB::7 (Uses)'],
            $this->getInheritsAsString($astMap->getClassReferenceByClassName(ClassLikeName::fromFQCN(BasicDependencyTraitC::class)))
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyTraitA::10 (Uses)',
                'Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyTraitB::11 (Uses)',
            ],
            $this->getInheritsAsString($astMap->getClassReferenceByClassName(ClassLikeName::fromFQCN(BasicDependencyTraitD::class)))
        );

        self::assertArrayValuesEquals(
            ['Tests\Qossmic\Deptrac\AstRunner\Visitor\Fixtures\BasicDependency\BasicDependencyTraitA::15 (Uses)'],
            $this->getInheritsAsString($astMap->getClassReferenceByClassName(ClassLikeName::fromFQCN(BasicDependencyTraitClass::class)))
        );
    }

    public function testIssue319(): void
    {
        $astMap = $this->getAstMap(__DIR__.'/Fixtures/Issue319.php');

        self::assertSame(
            [
                'Foo\Exception',
                'Foo\RuntimeException',
                'LogicException',
            ],
            array_map(
                static function (AstMap\AstDependency $dependency) {
                    return $dependency->getTokenName()->toString();
                },
                $astMap->getAstFileReferences()[__DIR__.'/Fixtures/Issue319.php']->getDependencies()
            )
        );
    }

    /**
     * @return string[]
     */
    private function getInheritsAsString(?AstMap\AstClassReference $classReference): array
    {
        if (null === $classReference) {
            return [];
        }

        return array_map('strval', $classReference->getInherits());
    }
}
