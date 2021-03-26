<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

final class ClassReferenceBuilder
{
    /** @var string */
    private $filepath;

    /** @var string */
    private $classLikeName;

    /** @var AstInherit[] */
    private $inherits = [];

    /** @var AstDependency[] */
    private $dependencies = [];

    private function __construct(string $filepath, string $classLikeName)
    {
        $this->filepath = $filepath;
        $this->classLikeName = $classLikeName;
    }

    public static function create(string $filepath, string $classLikeName): self
    {
        return new self($filepath, $classLikeName);
    }

    /** @internal */
    public function build(): AstClassReference
    {
        return new AstClassReference(
            ClassLikeName::fromFQCN($this->classLikeName),
            $this->inherits,
            $this->dependencies
        );
    }

    public function extends(string $classLikeName, int $occursAtLine): self
    {
        $this->inherits[] = AstInherit::newExtends(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function implements(string $classLikeName, int $occursAtLine): self
    {
        $this->inherits[] = AstInherit::newImplements(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function trait(string $classLikeName, int $occursAtLine): self
    {
        $this->inherits[] = AstInherit::newTraitUse(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function instanceof(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::instanceofExpr(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function parameter(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::parameter(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function newStatement(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::newStmt(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function staticProperty(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::staticProperty(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function staticMethod(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::staticMethod(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function returnType(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::returnType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function catchStmt(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::catchStmt(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function variable(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::variable(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function throwStatement(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::throwStmt(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function anonymousClassExtends(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::anonymousClassExtends(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function anonymousClassImplements(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::anonymousClassImplements(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function constFetch(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::constFetch(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function methodCall(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::methodCall(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }
}
