<?php

declare(strict_types=1);

namespace Qossmic\Deptrac;

use Qossmic\Deptrac\AstRunner\AstMap\TokenName;

interface TokenLayerResolverInterface
{
    /**
     * @return string[]
     */
    public function getLayersByTokenName(TokenName $tokenName): array;
}
