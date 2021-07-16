<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

/**
 * @psalm-immutable
 */
final class FileName implements TokenName
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function getFilepath(): string
    {
        return $this->toString();
    }
}
