<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\DependencyEmitter;

use Qossmic\Deptrac\AstRunner\AstParser\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\AstRunner\AstParser\BetterReflection\Factory;
use Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser\ParserFactory;
use Qossmic\Deptrac\AstRunner\AstRunner;
use Qossmic\Deptrac\AstRunner\Resolver\ClassMethodResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeResolver;
use Qossmic\Deptrac\Dependency\DependencyInterface;
use Qossmic\Deptrac\Dependency\Result;
use Qossmic\Deptrac\DependencyEmitter\DependencyEmitterInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

trait EmitterTrait
{
    public function getDeps(DependencyEmitterInterface $emitter, string $file): array
    {
        $phpParser = ParserFactory::createParser();
        $factory = new Factory(
            $phpParser,
            [dirname($file)],
            []
        );

        $typeResolver = new TypeResolver();
        $parser = new NikicPhpParser(
            $phpParser,
            new AstFileReferenceInMemoryCache(),
            $typeResolver,
            new ClassMethodResolver($typeResolver, $factory->create())
        );
        $astMap = (new AstRunner(new EventDispatcher(), $parser))->createAstMapByFiles([$file]);
        $result = new Result();

        $emitter->applyDependencies($astMap, $result);

        return array_map(
            static function (DependencyInterface $d) {
                return sprintf('%s:%d on %s',
                    $d->getClassLikeNameA()->toString(),
                    $d->getFileOccurrence()->getLine(),
                    $d->getClassLikeNameB()->toString()
                );
            },
            $result->getDependenciesAndInheritDependencies()
        );
    }
}
