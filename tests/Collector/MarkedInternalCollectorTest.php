<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Collector;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\Collector\MarkedInternalCollector;

final class MarkedInternalCollectorTest extends TestCase
{
    public function testGetType(): void
    {
        self::assertSame('markedInternal', (new MarkedInternalCollector())->getType());
    }

    public function testSatisfy(): void
    {
        $internalFooFileReferenceBuilder = AstMap\FileReferenceBuilder::create('internal_foo.php');
        $internalFooFileReferenceBuilder
            ->newClassLike('App\Internal\Foo')
            ->markAsInternal();

        $internalFooFileReference = $internalFooFileReferenceBuilder->build();

        $classReferences = $internalFooFileReference->getAstClassReferences();

        self::assertTrue($classReferences[0]->isInternal());
    }

    public function testDoesNotSatisfy(): void
    {
        $fooFileReferenceBuilder = AstMap\FileReferenceBuilder::create('foo.php');
        $fooFileReferenceBuilder
            ->newClassLike('App\Foo');

        $fooFileReference = $fooFileReferenceBuilder->build();

        $classReferences = $fooFileReference->getAstClassReferences();

        self::assertFalse($classReferences[0]->isInternal());
    }
}
