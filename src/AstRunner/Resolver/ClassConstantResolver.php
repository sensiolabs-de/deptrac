<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\Resolver;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use Qossmic\Deptrac\AstRunner\AstMap\ReferenceBuilder;

class ClassConstantResolver implements DependencyResolver
{
    public function processNode(Node $node, ReferenceBuilder $referenceBuilder, TypeScope $typeScope): void
    {
        if (!$node instanceof ClassConstFetch || !$node->class instanceof Name || $node->class->isSpecialClassName()) {
            return;
        }

        $referenceBuilder->constFetch($node->class->toCodeString(), $node->class->getLine());
    }
}
