<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\Resolver;

use phpDocumentor\Reflection\Type;
use PhpParser\Node;
use PHPStan\BetterReflection\BetterReflection;
use Qossmic\Deptrac\AstRunner\AstMap\ClassReferenceBuilder;

class ClassMethodResolver implements ClassDependencyResolver
{
    private $typeResolver;
    private $betterReflection;

    public function __construct(TypeResolver $typeResolver, BetterReflection $betterReflection)
    {
        $this->typeResolver = $typeResolver;
        $this->betterReflection = $betterReflection;
    }

    public function processNode(Node $node, ClassReferenceBuilder $classReferenceBuilder, TypeScope $typeScope): void
    {
        if (!$node instanceof Node\Stmt\ClassMethod) {
            return;
        }

        $typeScope = $typeScope->enterClassMethod();

        if (null !== $node->returnType) {
            foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, $node->returnType) as $classLikeName) {
                $classReferenceBuilder->returnType($classLikeName, $node->returnType->getLine());
            }
        }

        if (null === $node->stmts) {
            return;
        }

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof Node\Stmt\Expression) {
                continue;
            }

            if ($stmt->expr instanceof Node\Expr\Assign && $stmt->expr->var instanceof Node\Expr\Variable) {
                $this->resolveVariableAssign($stmt->expr, $typeScope);
            }

            if ($stmt->expr instanceof Node\Expr\MethodCall && $stmt->expr->var instanceof Node\Expr\Variable) {
                $this->resolveMethodCall($stmt->expr, $typeScope, $classReferenceBuilder);
            }
        }
    }

    private function resolveVariableAssign(Node\Expr\Assign $assignExpr, TypeScope $typeScope): void
    {
        $var = $assignExpr->var;
        if (!$var instanceof Node\Expr\Variable) {
            return;
        }

        $varName = $var->name;
        if (!is_string($varName)) {
            return;
        }

        $assignExprExpr = $assignExpr->expr;

        if ($assignExprExpr instanceof Node\Expr\New_ && $assignExprExpr->class instanceof Node\Name) {
            $types = $this->typeResolver->resolvePHPParserTypes($typeScope, $assignExprExpr->class);
            if ([] !== $types) {
                $typeScope->assignVariable($varName, ...$types);
            }
        } elseif ($assignExprExpr instanceof Node\Expr\MethodCall && $assignExprExpr->name instanceof Node\Identifier && $assignExprExpr->var instanceof Node\Expr\Variable) {
            $varNameUsedForMethodCall = $assignExprExpr->var->name;
            if (!is_string($varNameUsedForMethodCall)) {
                return;
            }

            $varTypes = $typeScope->getVariable($varNameUsedForMethodCall);
            $method = $assignExprExpr->name->toString();

            if ([] !== $varTypes) {
                $types = [];
                foreach ($varTypes as $varType) {
                    $types[] = $this->resolveMethodReturnTypes($varType, $method);
                }
                $typeScope->assignVariable($varName, ...array_merge([], ...$types));
            }
        }
    }

    /**
     * @return string[]
     */
    private function resolveMethodReturnTypes(string $class, string $method): array
    {
        $classReflection = $this->betterReflection->classReflector()->reflect($class);

        if (!$classReflection->hasMethod($method)) {
            return [];
        }

        $types = [];
        $methodReflection = $classReflection->getMethod($method);

        $returnType = $methodReflection->getReturnType();
        if (null !== $returnType) {
            $types[] = $returnType->__toString();
        }

        try {
            $types = array_merge($types, array_map(static function (Type $type) {
                return $type->__toString();
            }, $methodReflection->getDocBlockReturnTypes()));
        } catch (\Throwable $e) {
            // ignore
        }

        return $types;
    }

    private function resolveMethodCall(
        Node\Expr\MethodCall $methodCall,
        TypeScope $typeScope,
        ClassReferenceBuilder $classReferenceBuilder
    ): void {
        $var = $methodCall->var;
        if (!$var instanceof Node\Expr\Variable) {
            return;
        }

        $varName = $var->name;
        if (!is_string($varName)) {
            return;
        }

        $varTypes = $typeScope->getVariable($varName);
        foreach ($varTypes as $varType) {
            $classReferenceBuilder->methodCall($varType, $methodCall->getStartLine());
        }
    }
}
