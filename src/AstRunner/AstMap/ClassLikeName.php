<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

/**
 * @psalm-immutable
 */
final class ClassLikeName implements TokenName
{
    private string $className;

    private function __construct(string $className)
    {
        $this->className = $className;
    }

    public static function fromFQCN(string $className): self
    {
        return new self(ltrim($className, '\\'));
    }

    public function match(string $pattern): bool
    {
        return 1 === preg_match($pattern, $this->className);
    }

    public function toString(): string
    {
        return $this->className;
    }

    public function equals(self $classLikeName): bool
    {
        return $this->className === $classLikeName->className;
    }
}
