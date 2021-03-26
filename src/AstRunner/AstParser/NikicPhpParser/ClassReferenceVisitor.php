<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Qossmic\Deptrac\AstRunner\AstMap\FileReferenceBuilder;
use Qossmic\Deptrac\AstRunner\Resolver\ClassDependencyResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeScope;

class ClassReferenceVisitor extends NodeVisitorAbstract
{
    /** @var FileReferenceBuilder */
    private $fileReferenceBuilder;

    /** @var ClassDependencyResolver[] */
    private $classDependencyResolvers;

    /** @var TypeScope */
    private $currentTypeScope;

    /** @var TypeResolver */
    private $typeResolver;

    public function __construct(FileReferenceBuilder $fileReferenceBuilder, TypeResolver $typeResolver, ClassDependencyResolver ...$classDependencyResolvers)
    {
        $this->currentTypeScope = new TypeScope('');
        $this->fileReferenceBuilder = $fileReferenceBuilder;
        $this->classDependencyResolvers = $classDependencyResolvers;
        $this->typeResolver = $typeResolver;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentTypeScope = new TypeScope($node->name ? $node->name->toCodeString() : '');
        }

        if (!$node instanceof Node\Stmt\ClassLike) {
            return null;
        }

        if (isset($node->namespacedName)) {
            $className = $node->namespacedName->toCodeString();
        } elseif ($node->name instanceof Node\Identifier) {
            $className = $node->name->toString();
        } else {
            return null; // map anonymous classes on current class
        }

        $classReferenceBuilder = $this->fileReferenceBuilder->newClassLike($className);

        if ($node instanceof Node\Stmt\Class_) {
            if ($node->extends instanceof Node\Name) {
                $classReferenceBuilder->extends($node->extends->toCodeString(), $node->extends->getLine());
            }
            foreach ($node->implements as $implement) {
                $classReferenceBuilder->implements($implement->toCodeString(), $implement->getLine());
            }
        }

        if ($node instanceof Node\Stmt\Interface_) {
            foreach ($node->extends as $extend) {
                $classReferenceBuilder->implements($extend->toCodeString(), $extend->getLine());
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Use_ && Node\Stmt\Use_::TYPE_NORMAL === $node->type) {
            foreach ($node->uses as $use) {
                $this->currentTypeScope->addUse($use->name->toString(), $use->getAlias()->toString());
                $this->fileReferenceBuilder->use($use->name->toString(), $use->name->getLine());
            }
        }

        if ($node instanceof Node\Stmt\GroupUse) {
            foreach ($node->uses as $use) {
                if (Node\Stmt\Use_::TYPE_NORMAL === $use->type) {
                    $classLikeName = $node->prefix->toString().'\\'.$use->name->toString();
                    $this->currentTypeScope->addUse($classLikeName, $use->getAlias()->toString());
                    $this->fileReferenceBuilder->use($classLikeName, $use->name->getLine());
                }
            }
        }

        if (!$this->fileReferenceBuilder->hasCurrentClassLike()) {
            return null;
        }

        $classReferenceBuilder = $this->fileReferenceBuilder->currentClassLike();

        if ($node instanceof Node\Stmt\TraitUse) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, ...$node->traits) as $classLikeName) {
                $classReferenceBuilder->trait($classLikeName, $node->getLine());
            }
        }

        if ($node instanceof Node\Expr\Instanceof_ && $node->class instanceof Node\Name) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->class) as $classLikeName) {
                $classReferenceBuilder->instanceof($classLikeName, $node->class->getLine());
            }
        }

        if ($node instanceof Node\Param && null !== $node->type) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->type) as $classLikeName) {
                $classReferenceBuilder->parameter($classLikeName, $node->type->getLine());
            }
        }

        if ($node instanceof Node\Expr\New_ && $node->class instanceof Node\Name) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->class) as $classLikeName) {
                $classReferenceBuilder->newStatement($classLikeName, $node->class->getLine());
            }
        }

        if ($node instanceof Node\Expr\StaticPropertyFetch && $node->class instanceof Node\Name) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->class) as $classLikeName) {
                $classReferenceBuilder->staticProperty($classLikeName, $node->class->getLine());
            }
        }

        if ($node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->class) as $classLikeName) {
                $classReferenceBuilder->staticMethod($classLikeName, $node->class->getLine());
            }
        }

        if ($node instanceof Node\Expr\Closure && null !== $node->returnType) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, $node->returnType) as $classLikeName) {
                $classReferenceBuilder->returnType($classLikeName, $node->returnType->getLine());
            }
        }

        if ($node instanceof Node\Stmt\Catch_) {
            foreach ($this->typeResolver->resolvePHPParserTypes($this->currentTypeScope, ...$node->types) as $classLikeName) {
                $classReferenceBuilder->catchStmt($classLikeName, $node->getLine());
            }
        }

        foreach ($this->classDependencyResolvers as $resolver) {
            $resolver->processNode($node, $classReferenceBuilder, $this->currentTypeScope);
        }

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        return null;
    }
}
