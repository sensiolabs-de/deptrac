<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use Qossmic\Deptrac\AstRunner\AstMap\AstClassReference;
use Qossmic\Deptrac\AstRunner\AstMap\AstFileReference;
use Qossmic\Deptrac\AstRunner\AstMap\FileReferenceBuilder;
use Qossmic\Deptrac\AstRunner\AstParser\AstFileReferenceCache;
use Qossmic\Deptrac\AstRunner\AstParser\AstParser;
use Qossmic\Deptrac\AstRunner\Resolver\ClassDependencyResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeResolver;
use Qossmic\Deptrac\File\FileReader;
use Qossmic\Deptrac\ShouldNotHappenException;

class NikicPhpParser implements AstParser
{
    /**
     * @var array<string, Node\Stmt\ClassLike>
     */
    private static $classAstMap = [];

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var AstFileReferenceCache
     */
    private $cache;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var ClassDependencyResolver[]
     */
    private $classDependencyResolvers;

    public function __construct(
        Parser $parser,
        AstFileReferenceCache $cache,
        TypeResolver $typeResolver,
        ClassDependencyResolver ...$classDependencyResolvers
    ) {
        $this->parser = $parser;
        $this->cache = $cache;
        $this->typeResolver = $typeResolver;
        $this->classDependencyResolvers = $classDependencyResolvers;
    }

    public function parseFile(string $file): AstFileReference
    {
        if (null !== $fileReference = $this->cache->get($file)) {
            return $fileReference;
        }

        $fileReferenceBuilder = FileReferenceBuilder::create($file);
        $visitor = new ClassReferenceVisitor($fileReferenceBuilder, $this->typeResolver, ...$this->classDependencyResolvers);

        $nodes = $this->parser->parse(FileReader::read($file));
        if (null === $nodes) {
            throw new ShouldNotHappenException();
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        $fileReference = $fileReferenceBuilder->build();
        $this->cache->set($fileReference);

        return $fileReference;
    }

    public function getAstForClassReference(AstClassReference $classReference): ?Node\Stmt\ClassLike
    {
        $classLikeName = $classReference->getClassLikeName()->toString();

        if (isset(self::$classAstMap[$classLikeName])) {
            return self::$classAstMap[$classLikeName];
        }

        $astFileReference = $classReference->getFileReference();

        if (null === $astFileReference) {
            return null;
        }

        $findingVisitor = new FindingVisitor(
            static function (Node $node): bool {
                return $node instanceof Node\Stmt\ClassLike;
            }
        );

        $nodes = $this->parser->parse(FileReader::read($astFileReference->getFilepath()));
        if (null === $nodes) {
            throw new ShouldNotHappenException();
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor($findingVisitor);
        $traverser->traverse($nodes);

        /** @var Node\Stmt\ClassLike[] $classLikeNodes */
        $classLikeNodes = $findingVisitor->getFoundNodes();

        foreach ($classLikeNodes as $classLikeNode) {
            if (isset($classLikeNode->namespacedName)) {
                $className = $classLikeNode->namespacedName->toCodeString();
            } elseif ($classLikeNode->name instanceof Node\Identifier) {
                $className = $classLikeNode->name->toString();
            } else {
                continue;
            }

            self::$classAstMap[$className] = $classLikeNode;
        }

        return self::$classAstMap[$classLikeName] ?? null;
    }
}
