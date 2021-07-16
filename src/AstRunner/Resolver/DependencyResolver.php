<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\Resolver;

use PhpParser\Node;
use Qossmic\Deptrac\AstRunner\AstMap\ReferenceBuilder;

interface DependencyResolver
{
    public function processNode(Node $node, ReferenceBuilder $referenceBuilder, TypeScope $typeScope): void;
}
