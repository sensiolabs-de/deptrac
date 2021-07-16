<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

/**
 * @psalm-immutable
 */
class AstDependency
{
    //TODO: Replace with ENUM in PHP 8.1
    public const USE = 'use';
    public const RETURN_TYPE = 'returntype';
    public const PARAMETER = 'parameter';
    public const NEW = 'new';
    public const STATIC_PROPERTY = 'static_property';
    public const STATIC_METHOD = 'static_method';
    public const INSTANCEOF = 'instanceof';
    public const CATCH = 'catch';
    public const VARIABLE = 'variable';
    public const THROW = 'throw';
    public const CONST = 'const';
    public const ANONYMOUS_CLASS_EXTENDS = 'anonymous_class_extends';
    public const ANONYMOUS_CLASS_IMPLEMENTS = 'anonymous_class_implements';
    public const ANONYMOUS_CLASS_TRAIT = 'anonymous_class_trait';
    public const ATTRIBUTE = 'attribute';
    public const SUPERGLOBAL_VARIABLE = 'superglobal_variable';

    private TokenName $tokenName;
    private FileOccurrence $fileOccurrence;
    private string $type;

    private function __construct(TokenName $tokenName, FileOccurrence $fileOccurrence, string $type)
    {
        $this->tokenName = $tokenName;
        $this->fileOccurrence = $fileOccurrence;
        $this->type = $type;
    }

    public function getTokenName(): TokenName
    {
        return $this->tokenName;
    }

    public function getFileOccurrence(): FileOccurrence
    {
        return $this->fileOccurrence;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public static function fromType(TokenName $tokenName, FileOccurrence $fileOccurrence, string $type): self
    {
        return new self($tokenName, $fileOccurrence, $type);
    }
}
