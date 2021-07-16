<?php

declare(strict_types=1);

namespace Qossmic\Deptrac;

use Qossmic\Deptrac\AstRunner\AstMap\ClassLikeName;
use Qossmic\Deptrac\AstRunner\AstRunner;
use Qossmic\Deptrac\Configuration\Configuration;

class ClassLikeAnalyser
{
    private AstRunner $astRunner;
    private FileResolver $fileResolver;
    private ClassLikeLayerResolverFactory $classLikeLayerResolverFactory;

    public function __construct(
        AstRunner $astRunner,
        FileResolver $fileResolver,
        ClassLikeLayerResolverFactory $classLikeLayerResolverFactory
    ) {
        $this->astRunner = $astRunner;
        $this->fileResolver = $fileResolver;
        $this->classLikeLayerResolverFactory = $classLikeLayerResolverFactory;
    }

    /**
     * @return string[]
     */
    public function analyse(Configuration $configuration, ClassLikeName $classLikeName): array
    {
        $astMap = $this->astRunner->createAstMapByFiles($this->fileResolver->resolve($configuration), $configuration->getAnalyzer());

        return $this->classLikeLayerResolverFactory
            ->create($configuration, $astMap)
            ->getLayersByTokenName($classLikeName);
    }
}
