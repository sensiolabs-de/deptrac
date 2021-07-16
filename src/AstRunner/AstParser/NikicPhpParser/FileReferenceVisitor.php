<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser;

use PhpParser\Node;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Qossmic\Deptrac\AstRunner\AstMap\ClassReferenceBuilder;
use Qossmic\Deptrac\AstRunner\AstMap\FileReferenceBuilder;
use Qossmic\Deptrac\AstRunner\AstMap\ReferenceBuilder;
use Qossmic\Deptrac\AstRunner\Resolver\DependencyResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeScope;

class FileReferenceVisitor extends NodeVisitorAbstract
{
    private const SUPERGLOBALS = [
        'GLOBALS',
        '_SERVER',
        '_GET',
        '_POST',
        '_FILES',
        '_COOKIE',
        '_SESSION',
        '_REQUEST',
        '_ENV',
    ];

    private FileReferenceBuilder $fileReferenceBuilder;

    /** @var DependencyResolver[] */
    private array $dependencyResolvers;

    private TypeScope $currentTypeScope;

    private TypeResolver $typeResolver;
    private Lexer $lexer;
    private PhpDocParser $docParser;

    private ReferenceBuilder $currentReference;

    public function __construct(FileReferenceBuilder $fileReferenceBuilder, TypeResolver $typeResolver, DependencyResolver ...$dependencyResolvers)
    {
        $this->currentTypeScope = new TypeScope('');
        $this->lexer = new Lexer();
        $this->docParser = new PhpDocParser(new TypeParser(), new ConstExprParser());
        $this->fileReferenceBuilder = $fileReferenceBuilder;
        $this->dependencyResolvers = $dependencyResolvers;
        $this->typeResolver = $typeResolver;
        $this->currentReference = $fileReferenceBuilder;
    }

    /**
     * @return string[]
     */
    private function templatesFromDocs(
        Node $node
    ): array {
        $docComment = $node->getDocComment();
        if (null === $docComment) {
            return [];
        }
        $tokens = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $docNode = $this->docParser->parse($tokens);

        return array_map(static fn (TemplateTagValueNode $tag): string => $tag->name, $docNode->getTemplateTagValues());
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Namespace_) {
            $this->currentTypeScope = new TypeScope($node->name ? $node->name->toCodeString() : '');

            return null;
        }

        if ($node instanceof Node\Stmt\Function_) {
            $name = isset($node->namespacedName) ? $node->namespacedName->toCodeString() : $node->name->toString();
            $this->enterFunction($name, $node);
            foreach ($node->getAttrGroups() as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $attribute) as $classLikeName) {
                        $this->currentReference->attribute($classLikeName, $attribute->getLine());
                    }
                }
            }

            return null;
        }

        if ($node instanceof ClassLike) {
            $name = $this->getClassReferenceName($node);
            if (null !== $name) {
                $this->enterClassLike($name, $node);
            }
            foreach ($node->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $attribute->name) as $classLikeName) {
                        $this->currentReference->attribute($classLikeName, $attribute->getLine());
                    }
                }
            }

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        //Resolve current reference scope
        if ($node instanceof Node\Stmt\Function_) {
            $this->currentReference = $this->fileReferenceBuilder;

            return null;
        }
        if ($node instanceof ClassLike && null !== $this->getClassReferenceName($node)) {
            $this->currentReference = $this->fileReferenceBuilder;

            return null;
        }

        //Resolve current type scope
        if ($node instanceof Use_ && Use_::TYPE_NORMAL === $node->type) {
            foreach ($node->uses as $use) {
                $this->currentTypeScope->addUse($use->name->toString(), $use->getAlias()->toString());
                $this->fileReferenceBuilder->useStatement($use->name->toString(), $use->name->getLine());
            }

            return null;
        }
        if ($node instanceof GroupUse) {
            foreach ($node->uses as $use) {
                if (Use_::TYPE_NORMAL === $use->type) {
                    $classLikeName = $node->prefix->toString().'\\'.$use->name->toString();
                    $this->currentTypeScope->addUse($classLikeName, $use->getAlias()->toString());
                    $this->fileReferenceBuilder->useStatement($classLikeName, $use->name->getLine());
                }
            }

            return null;
        }

        //Resolve code
        if ($node instanceof Node\Stmt\TraitUse && $this->currentReference instanceof ClassReferenceBuilder) {
            //TODO: Missing anonymous classes support
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, ...$node->traits) as $classLikeName) {
                $this->currentReference->trait($classLikeName, $node->getLine());
            }
        }

        if ($node instanceof Instanceof_ && $node->class instanceof Name) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->class) as $classLikeName) {
                $this->currentReference->instanceof($classLikeName, $node->class->getLine());
            }
        }

        if ($node instanceof Node\Expr\Variable && in_array($node->name, self::SUPERGLOBALS, true)) {
            $this->currentReference->superglobal($node->name, $node->getLine());
        }

        if ($node instanceof New_ && $node->class instanceof Name) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->class) as $classLikeName) {
                $this->currentReference->newStatement($classLikeName, $node->class->getLine());
            }
        }

        if ($node instanceof StaticPropertyFetch && $node->class instanceof Name) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->class) as $classLikeName) {
                $this->currentReference->staticProperty($classLikeName, $node->class->getLine());
            }
        }

        if ($node instanceof StaticCall && $node->class instanceof Name) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->class) as $classLikeName) {
                $this->currentReference->staticMethod($classLikeName, $node->class->getLine());
            }
        }

        if ($node instanceof Catch_) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, ...$node->types) as $classLikeName) {
                $this->currentReference->catchStmt($classLikeName, $node->getLine());
            }
        }

        if ($node instanceof Node\FunctionLike) {
            foreach ($node->getAttrGroups() as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $attribute->name) as $classLikeName) {
                        $this->currentReference->attribute($classLikeName, $attribute->getLine());
                    }
                }
            }
            foreach ($node->getParams() as $param) {
                if (null !== $param->type) {
                    foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $param->type) as $classLikeName) {
                        $this->currentReference->parameter($classLikeName, $param->type->getLine());
                    }
                }
            }
            $returnType = $node->getReturnType();
            if (null !== $returnType) {
                foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $returnType) as $classLikeName) {
                    $this->currentReference->returnType($classLikeName, $returnType->getLine());
                }
            }
        }

        if ($node instanceof Property) {
            foreach ($node->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $attribute->name) as $classLikeName) {
                        $this->currentReference->attribute($classLikeName, $attribute->getLine());
                    }
                }
            }
            if (null !== $node->type) {
                foreach ($this->typeResolver->resolvePropertyType($node->type) as $type) {
                    $this->currentReference->variable($type, $node->type->getStartLine());
                }
            }
        }

        foreach ($this->dependencyResolvers as $resolver) {
            $resolver->processNode($node, $this->currentReference, $this->currentTypeScope);
        }

        return null;
    }

    private function enterClassLike(string $name, ClassLike $node): void
    {
        $this->currentReference =
            $this->fileReferenceBuilder->newClassLike($name, $this->templatesFromDocs($node));

        if ($node instanceof Class_) {
            if ($node->extends instanceof Name) {
                $this->currentReference->extends($node->extends->toCodeString(), $node->extends->getLine());
            }
            foreach ($node->implements as $implement) {
                $this->currentReference->implements($implement->toCodeString(), $implement->getLine());
            }
        }

        if ($node instanceof Interface_) {
            foreach ($node->extends as $extend) {
                $this->currentReference->implements($extend->toCodeString(), $extend->getLine());
            }
        }
    }

    private function enterFunction(string $name, Node\Stmt\Function_ $node): void
    {
        $this->currentReference = $this->fileReferenceBuilder->newFunction($name);

        foreach ($node->getParams() as $param) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $param) as $classLikeName) {
                $this->currentReference->parameter($classLikeName, $param->getLine());
            }
        }

        $returnType = $node->getReturnType();
        if (null !== $returnType) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $returnType) as $classLikeName) {
                $this->currentReference->returnType($classLikeName, $returnType->getLine());
            }
        }
    }

    private function getClassReferenceName(ClassLike $node): ?string
    {
        if (isset($node->namespacedName)) {
            return $node->namespacedName->toCodeString();
        }

        if ($node->name instanceof Identifier) {
            return $node->name->toString();
        }

        return null;
    }
}
