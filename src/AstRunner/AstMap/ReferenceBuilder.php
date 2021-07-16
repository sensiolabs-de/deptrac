<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

abstract class ReferenceBuilder
{
    /** @var string[] */
    protected array $tokenTemplates;

    protected string $filepath;

    /** @var AstDependency[] */
    protected array $dependencies = [];

    /**
     * @param string[] $tokenTemplates
     */
    protected function __construct(array $tokenTemplates, string $filepath)
    {
        $this->tokenTemplates = $tokenTemplates;
        $this->filepath = $filepath;
    }

    /**
     * @return string[]
     */
    final public function getTokenTemplates(): array
    {
        return $this->tokenTemplates;
    }

    public function variable(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::VARIABLE
        );

        return $this;
    }

    public function superglobal(string $superglobalName, int $occursAtLine): void
    {
        $this->dependencies[] = AstDependency::fromType(
            new SuperGlobalName($superglobalName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::SUPERGLOBAL_VARIABLE
        );
    }

    public function returnType(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::RETURN_TYPE
        );

        return $this;
    }

    public function throwStatement(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::THROW
        );

        return $this;
    }

    public function anonymousClassExtends(string $classLikeName, int $occursAtLine): void
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::ANONYMOUS_CLASS_EXTENDS
        );
    }

    public function anonymousClassTrait(string $classLikeName, int $occursAtLine): void
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::ANONYMOUS_CLASS_TRAIT
        );
    }

    public function constFetch(string $classLikeName, int $occursAtLine): void
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::CONST
        );
    }

    public function anonymousClassImplements(string $classLikeName, int $occursAtLine): void
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::ANONYMOUS_CLASS_IMPLEMENTS
        );
    }

    public function parameter(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::PARAMETER
        );

        return $this;
    }

    public function attribute(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::ATTRIBUTE
        );

        return $this;
    }

    public function instanceof(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::INSTANCEOF
        );

        return $this;
    }

    public function newStatement(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::NEW
        );

        return $this;
    }

    public function staticProperty(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::STATIC_PROPERTY
        );

        return $this;
    }

    public function staticMethod(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::STATIC_METHOD
        );

        return $this;
    }

    public function catchStmt(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::CATCH
        );

        return $this;
    }
}
