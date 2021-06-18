<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Integration\Fixtures;

/**
 * @internal
 */
final class AnnotationDependency
{
    /**
     * @var AnnotationDependencyChild
     */
    public $property;

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $parameter
     *
     * @return AnnotationDependencyChild[]
     *
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     */
    public function test($parameter)
    {
        /** @var ?AnnotationDependencyChild $test */
        $test = null;

        /** @var AnnotationDependencyChild[] $children */
        $children = [];

        /** @var \Symfony\Component\Console\Exception\RuntimeException $fqn */
        $fqn = [];

        return [];
    }
}

final class AnnotationDependencyChild
{
}
