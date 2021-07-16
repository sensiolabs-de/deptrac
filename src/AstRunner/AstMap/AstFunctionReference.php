<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

/**
 * @psalm-immutable
 */
class AstFunctionReference implements AstTokenReference
{
    private ?AstFileReference $fileReference = null;
    /** @var AstDependency[] */
    private array $dependencies;
    private FunctionName $functionName;

    /**
     * @param AstDependency[] $dependencies
     */
    public function __construct(FunctionName $functionName, array $dependencies = [])
    {
        $this->functionName = $functionName;
        $this->dependencies = $dependencies;
    }

    /**
     * @return AstDependency[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function withFileReference(AstFileReference $astFileReference): self
    {
        $instance = clone $this;
        $instance->fileReference = $astFileReference;

        return $instance;
    }

    public function getFileReference(): ?AstFileReference
    {
        return $this->fileReference;
    }

    public function getTokenName(): TokenName
    {
        return $this->functionName;
    }
}
