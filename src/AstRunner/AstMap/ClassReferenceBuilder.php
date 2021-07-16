<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

final class ClassReferenceBuilder extends ReferenceBuilder
{
    private string $classLikeName;

    /** @var AstInherit[] */
    private array $inherits = [];

    /**
     * @param string[] $tokenTemplates
     */
    protected function __construct(array $tokenTemplates, string $filepath, string $classLikeName)
    {
        parent::__construct($tokenTemplates, $filepath);
        $this->classLikeName = $classLikeName;
    }

    /**
     * @param string[] $classTemplates
     */
    public static function create(string $filepath, string $classLikeName, array $classTemplates): self
    {
        return new self($classTemplates, $filepath, $classLikeName);
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
}
