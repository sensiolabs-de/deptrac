<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstParser\BetterReflection;

use PhpParser\Parser;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\ConstantReflector;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

final class Factory
{
    private $parser;
    private $directories;
    private $composerAutoloaderFiles;

    /**
     * @param string[] $directories
     * @param string[] $composerAutoloaderFiles
     */
    public function __construct(Parser $parser, array $directories, array $composerAutoloaderFiles)
    {
        $this->parser = $parser;
        $this->directories = $directories;
        $this->composerAutoloaderFiles = $composerAutoloaderFiles;
    }

    public function create(): BetterReflection
    {
        BetterReflection::populate(
            $this->sourceLocator(),
            $this->classReflector(),
            $this->functionReflector(),
            $this->constantReflector(),
            $this->parser,
            $this->sourceStubber()
        );

        return new BetterReflection();
    }

    private function sourceLocator(): SourceLocator
    {
        $astLocator = $this->astLocator();
        $sourceStubber = $this->sourceStubber();

        return new MemoizingSourceLocator(new AggregateSourceLocator([
            new PhpInternalSourceLocator($astLocator, $sourceStubber),
            new DirectoriesSourceLocator($this->directories, $astLocator),
            $this->composerAutoloaderLocator(),
        ]));
    }

    private function classReflector(): ClassReflector
    {
        return new ClassReflector($this->sourceLocator());
    }

    private function functionReflector(): FunctionReflector
    {
        return new FunctionReflector($this->sourceLocator(), $this->classReflector());
    }

    private function constantReflector(): ConstantReflector
    {
        return new ConstantReflector($this->sourceLocator(), $this->classReflector());
    }

    private function astLocator(): AstLocator
    {
        return new AstLocator($this->parser, function (): FunctionReflector {
            return $this->functionReflector();
        });
    }

    private function sourceStubber(): SourceStubber
    {
        return new AggregateSourceStubber(
            new PhpStormStubsSourceStubber($this->parser, BetterReflection::$phpVersion),
            new ReflectionSourceStubber()
        );
    }

    private function composerAutoloaderLocator(): AggregateSourceLocator
    {
        $astLocator = $this->astLocator();

        return new AggregateSourceLocator(
            array_map(static function (string $composerAutoloadFile) use ($astLocator): ComposerSourceLocator {
                return new ComposerSourceLocator(require $composerAutoloadFile, $astLocator);
            }, $this->composerAutoloaderFiles)
        );
    }
}
