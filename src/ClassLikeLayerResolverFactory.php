<?php

declare(strict_types=1);

namespace Qossmic\Deptrac;

use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\Collector\Registry;
use Qossmic\Deptrac\Configuration\Configuration;
use Qossmic\Deptrac\Configuration\ParameterResolver;

class ClassLikeLayerResolverFactory
{
    private Registry $registry;
    private ParameterResolver $parameterResolver;

    public function __construct(Registry $registry, ParameterResolver $parameterResolver)
    {
        $this->registry = $registry;
        $this->parameterResolver = $parameterResolver;
    }

    public function create(Configuration $configuration, AstMap $astMap): TokenLayerResolverInterface
    {
        return new MemoizedTokenLayerResolver(
            new TokenLayerResolver(
                $configuration,
                $astMap,
                $this->registry,
                $this->parameterResolver
            )
        );
    }
}
