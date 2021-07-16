<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

final class FileReferenceBuilder extends ReferenceBuilder
{
    /** @var ClassReferenceBuilder[] */
    private array $classReferences = [];

    /** @var FunctionReferenceBuilder[] */
    private array $functionReferences = [];

    public static function create(string $filepath): self
    {
        return new self([], $filepath);
    }

    public function useStatement(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = AstDependency::fromType(
            ClassLikeName::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            AstDependency::USE
        );

        return $this;
    }

    /**
     * @param string[] $templateTypes
     */
    public function newClassLike(string $classLikeName, array $templateTypes = []): ClassReferenceBuilder
    {
        $classReference = ClassReferenceBuilder::create($this->filepath, $classLikeName, $templateTypes);
        $this->classReferences[] = $classReference;

        return $classReference;
    }

    public function newFunction(string $functionName): FunctionReferenceBuilder
    {
        $functionReference = FunctionReferenceBuilder::create($this->filepath, $functionName);
        $this->functionReferences[] = $functionReference;

        return $functionReference;
    }

    public function build(): AstFileReference
    {
        $classReferences = [];
        foreach ($this->classReferences as $classReference) {
            $classReferences[] = $classReference->build();
        }

        $functionReferences = [];
        foreach ($this->functionReferences as $functionReference) {
            $functionReferences[] = $functionReference->build();
        }

        return new AstFileReference($this->filepath, $classReferences, $functionReferences, $this->dependencies);
    }
}
