<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use Qossmic\Deptrac\AstRunner\AstMap\AstClassReference;
use Qossmic\Deptrac\AstRunner\AstMap\AstFileReference;
use Qossmic\Deptrac\AstRunner\AstMap\FileReferenceBuilder;
use Qossmic\Deptrac\AstRunner\AstParser\AstFileReferenceCache;
use Qossmic\Deptrac\AstRunner\AstParser\AstParser;
use Qossmic\Deptrac\AstRunner\Resolver\DependencyResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeResolver;
use Qossmic\Deptrac\Configuration\ConfigurationAnalyzer;
use Qossmic\Deptrac\File\FileReader;
use Qossmic\Deptrac\ShouldNotHappenException;

class NikicPhpParser implements AstParser
{
    /**
     * @var array<string, ClassLike>
     */
    private static array $classAstMap = [];

    private Parser $parser;

    private AstFileReferenceCache $cache;

    private TypeResolver $typeResolver;

    /**
     * @var DependencyResolver[]
     */
    private array $dependencyResolvers;

    private NodeTraverser $traverser;

    public function __construct(
        Parser $parser,
        AstFileReferenceCache $cache,
        TypeResolver $typeResolver,
        DependencyResolver ...$dependencyResolvers
    ) {
        $this->parser = $parser;
        $this->cache = $cache;
        $this->typeResolver = $typeResolver;
        $this->dependencyResolvers = $dependencyResolvers;

        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new NameResolver());
    }

    public function parseFile(string $file, ConfigurationAnalyzer $configuration): AstFileReference
    {
        if (null !== $fileReference = $this->cache->get($file)) {
            return $fileReference;
        }

        $fileReferenceBuilder = FileReferenceBuilder::create($file);
        $nodes = $this->parser->parse(FileReader::read($file));
        if (null === $nodes) {
            throw new ShouldNotHappenException();
        }

        $visitor = new FileReferenceVisitor($fileReferenceBuilder, $this->typeResolver, ...$this->dependencyResolvers);
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($nodes);
        $this->traverser->removeVisitor($visitor);

        $fileReference = $fileReferenceBuilder->build();
        $this->cache->set($fileReference);

        return $fileReference;
    }

    public function getAstForClassReference(AstClassReference $classReference): ?ClassLike
    {
        $classLikeName = $classReference->getTokenName()->toString();

        if (isset(self::$classAstMap[$classLikeName])) {
            return self::$classAstMap[$classLikeName];
        }

        $astFileReference = $classReference->getFileReference();

        if (null === $astFileReference) {
            return null;
        }

        $findingVisitor = new FindingVisitor(static fn (Node $node): bool => $node instanceof ClassLike);

        $nodes = $this->parser->parse(FileReader::read($astFileReference->getFilepath()));
        if (null === $nodes) {
            throw new ShouldNotHappenException();
        }

        $this->traverser->addVisitor($findingVisitor);
        $this->traverser->traverse($nodes);
        $this->traverser->removeVisitor($findingVisitor);

        /** @var ClassLike[] $classLikeNodes */
        $classLikeNodes = $findingVisitor->getFoundNodes();

        foreach ($classLikeNodes as $classLikeNode) {
            if (isset($classLikeNode->namespacedName)) {
                $className = $classLikeNode->namespacedName->toCodeString();
            } elseif ($classLikeNode->name instanceof Identifier) {
                $className = $classLikeNode->name->toString();
            } else {
                continue;
            }

            self::$classAstMap[$className] = $classLikeNode;
        }

        return self::$classAstMap[$classLikeName] ?? null;
    }
}
