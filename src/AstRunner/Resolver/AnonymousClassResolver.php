<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\Resolver;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use Qossmic\Deptrac\AstRunner\AstMap\ReferenceBuilder;

class AnonymousClassResolver implements DependencyResolver
{
    public function processNode(Node $node, ReferenceBuilder $referenceBuilder, TypeScope $typeScope): void
    {
        if (!$node instanceof Class_ || null !== $node->name) {
            return;
        }

        if ($node->extends instanceof Name) {
            $referenceBuilder->anonymousClassExtends($node->extends->toCodeString(), $node->extends->getLine());
        }

        foreach ($node->implements as $implement) {
            $referenceBuilder->anonymousClassImplements($implement->toCodeString(), $implement->getLine());
        }

        foreach ($node->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $referenceBuilder->anonymousClassTrait($trait->toCodeString(), $trait->getLine());
            }
        }
    }
}
