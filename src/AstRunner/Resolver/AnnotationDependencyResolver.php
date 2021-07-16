<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\Resolver;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Qossmic\Deptrac\AstRunner\AstMap\ReferenceBuilder;

class AnnotationDependencyResolver implements DependencyResolver
{
    private Lexer $lexer;
    private PhpDocParser $docParser;
    private TypeResolver $typeResolver;

    public function __construct(TypeResolver $typeResolver)
    {
        $this->lexer = new Lexer();
        $this->docParser = new PhpDocParser(new TypeParser(), new ConstExprParser());
        $this->typeResolver = $typeResolver;
    }

    public function processNode(Node $node, ReferenceBuilder $referenceBuilder, TypeScope $typeScope): void
    {
        if (!$node instanceof Property
            && !$node instanceof Variable
            && !$node instanceof ClassMethod
        ) {
            return;
        }

        $docComment = $node->getDocComment();
        if (!$docComment instanceof Doc) {
            return;
        }

        $tokens = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $docNode = $this->docParser->parse($tokens);
        $templateTypes = array_merge(
            array_map(
                static fn (TemplateTagValueNode $node): string => $node->name,
                $docNode->getTemplateTagValues()
            ),
            $referenceBuilder->getTokenTemplates()
        );

        foreach ($docNode->getVarTagValues() as $tag) {
            $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope, $templateTypes);

            foreach ($types as $type) {
                $referenceBuilder->variable($type, $docComment->getStartLine());
            }
        }

        foreach ($docNode->getParamTagValues() as $tag) {
            $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope, $templateTypes);

            foreach ($types as $type) {
                $referenceBuilder->parameter($type, $docComment->getStartLine());
            }
        }

        foreach ($docNode->getReturnTagValues() as $tag) {
            $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope, $templateTypes);

            foreach ($types as $type) {
                $referenceBuilder->returnType($type, $docComment->getStartLine());
            }
        }

        foreach ($docNode->getThrowsTagValues() as $tag) {
            $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope, $templateTypes);

            foreach ($types as $type) {
                $referenceBuilder->throwStatement($type, $docComment->getStartLine());
            }
        }
    }
}
