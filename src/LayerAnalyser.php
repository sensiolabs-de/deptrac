<?php

declare(strict_types=1);

namespace Qossmic\Deptrac;

use function in_array;
use Qossmic\Deptrac\AstRunner\AstRunner;
use Qossmic\Deptrac\Configuration\Configuration;
use const true;

class LayerAnalyser
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
    public function analyse(Configuration $configuration, string $layer): array
    {
        $astMap = $this->astRunner->createAstMapByFiles($this->fileResolver->resolve($configuration), $configuration->getAnalyzer());
        $classLikeLayerResolver = $this->classLikeLayerResolverFactory->create($configuration, $astMap);

        $tokenNames = [];

        foreach ($astMap->getAstClassReferences() as $classReference) {
            $classLikeName = $classReference->getTokenName();
            if (in_array($layer, $classLikeLayerResolver->getLayersByTokenName($classLikeName), true)) {
                $tokenNames[] = $classLikeName->toString();
            }
        }

        natcasesort($tokenNames);

        return array_values($tokenNames);
    }
}
