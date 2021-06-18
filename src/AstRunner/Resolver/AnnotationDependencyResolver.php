<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\Resolver;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Qossmic\Deptrac\AstRunner\AstMap\ClassReferenceBuilder;

class AnnotationDependencyResolver implements ClassDependencyResolver
{
    /** @var Lexer */
    private $lexer;
    /** @var PhpDocParser */
    private $docParser;
    /** @var TypeResolver */
    private $typeResolver;

    public function __construct(TypeResolver $typeResolver)
    {
        $this->lexer = new Lexer();
        $this->docParser = new PhpDocParser(new TypeParser(), new ConstExprParser());
        $this->typeResolver = $typeResolver;
    }

    public function processNode(Node $node, ClassReferenceBuilder $classReferenceBuilder, TypeScope $typeScope): void
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            $docComment = $node->getDocComment();
            if (!$docComment instanceof Doc) {
                return;
            }

            $tokens = new TokenIterator($this->lexer->tokenize($docComment->getText()));
            $docNode = $this->docParser->parse($tokens);

            if (count($docNode->getTagsByName('@internal')) > 0) {
                $classReferenceBuilder->markAsInternal();
            }
        }

        if (!$node instanceof Node\Stmt\Property
            && !$node instanceof Node\Expr\Variable
            && !$node instanceof Node\Stmt\ClassMethod
        ) {
            return;
        }

        $docComment = $node->getDocComment();
        if (!$docComment instanceof Doc) {
            return;
        }

        $tokens = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $docNode = $this->docParser->parse($tokens);

        foreach ($docNode->getParamTagValues() as $tag) {
            $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope);

            foreach ($types as $type) {
                $classReferenceBuilder->parameter($type, $docComment->getStartLine());
            }
        }

        foreach ($docNode->getVarTagValues() as $tag) {
            $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope);

            foreach ($types as $type) {
                $classReferenceBuilder->variable($type, $docComment->getStartLine());
            }
        }

        foreach ($docNode->getReturnTagValues() as $tag) {
            $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope);

            foreach ($types as $type) {
                $classReferenceBuilder->returnType($type, $docComment->getStartLine());
            }
        }

        foreach ($docNode->getThrowsTagValues() as $tag) {
            $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope);

            foreach ($types as $type) {
                $classReferenceBuilder->throwStatement($type, $docComment->getStartLine());
            }
        }
    }
}
