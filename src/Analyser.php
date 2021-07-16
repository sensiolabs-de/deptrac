<?php

namespace Qossmic\Deptrac;

use Qossmic\Deptrac\AstRunner\AstRunner;
use Qossmic\Deptrac\Configuration\Configuration;
use Qossmic\Deptrac\Dependency\Resolver;
use Qossmic\Deptrac\RulesetEngine\Context;

class Analyser
{
    private AstRunner $astRunner;
    private FileResolver $fileResolver;
    private Resolver $resolver;
    private RulesetEngine $rulesetEngine;
    private ClassLikeLayerResolverFactory $classLikeLayerResolverFactory;

    public function __construct(
        AstRunner $astRunner,
        FileResolver $fileResolver,
        Resolver $resolver,
        RulesetEngine $rulesetEngine,
        ClassLikeLayerResolverFactory $classLikeLayerResolverFactory
    ) {
        $this->astRunner = $astRunner;
        $this->fileResolver = $fileResolver;
        $this->resolver = $resolver;
        $this->rulesetEngine = $rulesetEngine;
        $this->classLikeLayerResolverFactory = $classLikeLayerResolverFactory;
    }

    public function analyse(Configuration $configuration): Context
    {
        $astMap = $this->astRunner->createAstMapByFiles($this->fileResolver->resolve($configuration), $configuration->getAnalyzer());
        $dependencyResult = $this->resolver->resolve($astMap, $configuration->getAnalyzer());
        $classLikeLayerResolver = $this->classLikeLayerResolverFactory->create($configuration, $astMap);

        return $this->rulesetEngine->process($dependencyResult, $classLikeLayerResolver, $configuration);
    }
}
