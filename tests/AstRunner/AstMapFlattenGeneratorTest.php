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
use Qossmic\Deptrac\AstRunner\Resolver\TypeResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceA;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceB;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceC;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceD;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceE;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceA;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceB;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceC;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceD;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceE;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseA;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseB;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseC;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA1;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA2;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceB;
use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceC;

final class AstMapFlattenGeneratorTest extends TestCase
{
    use ArrayAsserts;

    private function getAstMap(string $fixture): AstMap
    {
        $astRunner = new AstRunner(
            new EventDispatcher(),
            new NikicPhpParser(
                ParserFactory::createParser(),
                new AstFileReferenceInMemoryCache(),
                new TypeResolver()
            )
        );

        return $astRunner->createAstMapByFiles([__DIR__.'/Fixtures/BasicInheritance/'.$fixture.'.php']);
    }

    private function getInheritedInherits(string $class, AstMap $astMap): array
    {
        $inherits = [];
        foreach ($astMap->getClassInherits(ClassLikeName::fromFQCN($class)) as $v) {
            if (count($v->getPath()) > 0) {
                $inherits[] = (string) $v;
            }
        }

        return $inherits;
    }

    public function testBasicInheritance(): void
    {
        $astMap = $this->getAstMap('FixtureBasicInheritance');

        self::assertArrayValuesEquals(
            [],
            $this->getInheritedInherits(FixtureBasicInheritanceA::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [],
            $this->getInheritedInherits(FixtureBasicInheritanceB::class, $astMap)
        );

        self::assertArrayValuesEquals(
            ['Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceA::6 (Extends) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceB::7 (Extends))'],
            $this->getInheritedInherits(FixtureBasicInheritanceC::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceA::6 (Extends) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceC::8 (Extends) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceB::7 (Extends))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceB::7 (Extends) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceC::8 (Extends))',
            ],
            $this->getInheritedInherits(FixtureBasicInheritanceD::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceA::6 (Extends) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceD::9 (Extends) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceC::8 (Extends) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceB::7 (Extends))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceB::7 (Extends) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceD::9 (Extends) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceC::8 (Extends))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceC::8 (Extends) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceD::9 (Extends))',
            ],
            $this->getInheritedInherits(FixtureBasicInheritanceE::class, $astMap)
        );
    }

    public function testBasicInheritanceInterfaces(): void
    {
        $astMap = $this->getAstMap('FixtureBasicInheritanceInterfaces');

        self::assertArrayValuesEquals(
            [],
            $this->getInheritedInherits(FixtureBasicInheritanceInterfaceA::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [],
            $this->getInheritedInherits(FixtureBasicInheritanceInterfaceB::class, $astMap)
        );

        self::assertArrayValuesEquals(
            ['Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceA::6 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceB::7 (Implements))'],
            $this->getInheritedInherits(FixtureBasicInheritanceInterfaceC::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceA::6 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceC::8 (Implements) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceB::7 (Implements))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceB::7 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceC::8 (Implements))',
            ],
            $this->getInheritedInherits(FixtureBasicInheritanceInterfaceD::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceA::6 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceD::9 (Implements) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceC::8 (Implements) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceB::7 (Implements))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceB::7 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceD::9 (Implements) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceC::8 (Implements))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceC::8 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceInterfaceD::9 (Implements))',
            ],
            $this->getInheritedInherits(FixtureBasicInheritanceInterfaceE::class, $astMap)
        );
    }

    public function testBasicMultipleInheritanceInterfaces(): void
    {
        $astMap = $this->getAstMap('MultipleInheritanceInterfaces');

        self::assertArrayValuesEquals(
            [],
            $this->getInheritedInherits(MultipleInteritanceA1::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [],
            $this->getInheritedInherits(MultipleInteritanceA2::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [],
            $this->getInheritedInherits(MultipleInteritanceA::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA1::7 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA::8 (Implements))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA2::7 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA::8 (Implements))',
            ],
            $this->getInheritedInherits(MultipleInteritanceB::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA1::7 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceB::9 (Implements) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA::8 (Implements))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA1::8 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceB::9 (Implements))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA2::7 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceB::9 (Implements) -> Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA::8 (Implements))',
                'Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceA::8 (Implements) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\MultipleInteritanceB::9 (Implements))',
            ],
            $this->getInheritedInherits(MultipleInteritanceC::class, $astMap)
        );
    }

    public function testBasicMultipleInheritanceWithNoise(): void
    {
        $astMap = $this->getAstMap('FixtureBasicInheritanceWithNoise');

        self::assertArrayValuesEquals(
            [],
            $this->getInheritedInherits(FixtureBasicInheritanceWithNoiseA::class, $astMap)
        );

        self::assertArrayValuesEquals(
            [],
            $this->getInheritedInherits(FixtureBasicInheritanceWithNoiseB::class, $astMap)
        );

        self::assertArrayValuesEquals(
            ['Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseA::18 (Extends) (path: Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseB::19 (Extends))'],
            $this->getInheritedInherits(FixtureBasicInheritanceWithNoiseC::class, $astMap)
        );
    }
}
