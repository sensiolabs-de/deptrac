<?php 

namespace Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\BasicDependency;

trait BasicDependencyTraitA {}
trait BasicDependencyTraitB {}
trait BasicDependencyTraitC { use BasicDependencyTraitB; }

trait BasicDependencyTraitD {
    use BasicDependencyTraitA;
    use BasicDependencyTraitB;
}

final class BasicDependencyTraitClass {
    use BasicDependencyTraitA;
}
