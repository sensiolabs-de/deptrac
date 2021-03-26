<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

class AstDependency
{
    private $classLikeName;
    private $fileOccurrence;
    private $type;

    private function __construct(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence, string $type)
    {
        $this->classLikeName = $classLikeName;
        $this->fileOccurrence = $fileOccurrence;
        $this->type = $type;
    }

    public function getClassLikeName(): ClassLikeName
    {
        return $this->classLikeName;
    }

    public function getFileOccurrence(): FileOccurrence
    {
        return $this->fileOccurrence;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public static function useStmt(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'use');
    }

    public static function returnType(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'returntype');
    }

    public static function parameter(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'parameter');
    }

    public static function newStmt(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'new');
    }

    public static function staticProperty(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'static_property');
    }

    public static function staticMethod(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'static_method');
    }

    public static function instanceofExpr(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'instanceof');
    }

    public static function catchStmt(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'catch');
    }

    public static function variable(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'variable');
    }

    public static function throwStmt(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'throw');
    }

    public static function constFetch(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'const');
    }

    public static function anonymousClassExtends(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'anonymous_class_extends');
    }

    public static function anonymousClassImplements(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'anonymous_class_implements');
    }

    public static function methodCall(ClassLikeName $classLikeName, FileOccurrence $fileOccurrence): self
    {
        return new self($classLikeName, $fileOccurrence, 'method_call');
    }
}
