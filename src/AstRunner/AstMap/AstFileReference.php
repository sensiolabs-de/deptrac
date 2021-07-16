<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

/**
 * @psalm-immutable
 */
class AstFileReference implements AstTokenReference
{
    private string $filepath;

    /** @var AstClassReference[] */
    private array $classReferences;

    /** @var AstDependency[] */
    private array $dependencies;

    /** @var AstFunctionReference[] */
    private array $functionReferences;

    /**
     * @param AstClassReference[] $classReferences
     * @param AstDependency[]     $dependencies
     */
    public function __construct(string $filepath, array $classReferences, array $functionReferences, array $dependencies)
    {
        $this->filepath = $filepath;
        $this->dependencies = $dependencies;
        /** @psalm-suppress ImpureFunctionCall */
        $this->classReferences = array_map(
            fn (AstClassReference $classReference) => $classReference->withFileReference($this),
            $classReferences
        );
        /** @psalm-suppress ImpureFunctionCall */
        $this->functionReferences = array_map(
            fn (AstFunctionReference $functionReference) => $functionReference->withFileReference($this),
            $functionReferences
        );
    }

    public function getFilepath(): string
    {
        return $this->filepath;
    }

    /**
     * @return AstClassReference[]
     */
    public function getAstClassReferences(): array
    {
        return $this->classReferences;
    }

    /**
     * @return AstDependency[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return AstFunctionReference[]
     */
    public function getFunctionReferences(): array
    {
        return $this->functionReferences;
    }

    public function getFileReference(): ?AstFileReference
    {
        return $this;
    }

    public function getTokenName(): TokenName
    {
        return new FileName($this->getFilepath());
    }
}
