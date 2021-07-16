<?php

declare(strict_types=1);

namespace Qossmic\Deptrac;

use Qossmic\Deptrac\AstRunner\AstMap\TokenName;

class MemoizedTokenLayerResolver implements TokenLayerResolverInterface
{
    private TokenLayerResolverInterface $classLikeLayerResolver;

    /** @var array<string, string[]> */
    private array $layerNamesByClassCache = [];

    public function __construct(TokenLayerResolverInterface $classLikeLayerResolver)
    {
        $this->classLikeLayerResolver = $classLikeLayerResolver;
    }

    public function getLayersByTokenName(TokenName $tokenName): array
    {
        if (!isset($this->layerNamesByClassCache[$tokenName->toString()])) {
            $this->layerNamesByClassCache[$tokenName->toString()] = $this->classLikeLayerResolver->getLayersByTokenName($tokenName);
        }

        return $this->layerNamesByClassCache[$tokenName->toString()];
    }
}
