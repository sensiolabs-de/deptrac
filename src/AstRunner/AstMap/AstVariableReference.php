<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

/**
 * @psalm-immutable
 */
class AstVariableReference implements AstTokenReference
{
    private SuperGlobalName $tokenName;

    public function __construct(SuperGlobalName $tokenName)
    {
        $this->tokenName = $tokenName;
    }

    public function getFileReference(): ?AstFileReference
    {
        return null;
    }

    public function getTokenName(): TokenName
    {
        return $this->tokenName;
    }
}
