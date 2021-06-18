<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

class AstClassReference
{
    /** @var ClassLikeName */
    private $classLikeName;

    /** @var AstFileReference|null */
    private $fileReference;

    /** @var AstDependency[] */
    private $dependencies;

    /** @var AstInherit[] */
    private $inherits;

    /** @var bool */
    private $internal;

    /**
     * @param AstInherit[]    $inherits
     * @param AstDependency[] $dependencies
     */
    public function __construct(ClassLikeName $classLikeName, array $inherits = [], array $dependencies = [], bool $internal = false)
    {
        $this->classLikeName = $classLikeName;
        $this->dependencies = $dependencies;
        $this->inherits = $inherits;
        $this->internal = $internal;
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

    public function getClassLikeName(): ClassLikeName
    {
        return $this->classLikeName;
    }

    /**
     * @return AstDependency[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return AstInherit[]
     */
    public function getInherits(): array
    {
        return $this->inherits;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }
}
