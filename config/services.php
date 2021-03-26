<?php

declare(strict_types=1);

use PhpParser\Parser;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Qossmic\Deptrac\Analyser;
use Qossmic\Deptrac\AstRunner\AstParser\AstFileReferenceCache;
use Qossmic\Deptrac\AstRunner\AstParser\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\AstRunner\AstParser\BetterReflection\Factory;
use Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\AstRunner\AstParser\NikicPhpParser\ParserFactory;
use Qossmic\Deptrac\AstRunner\AstRunner;
use Qossmic\Deptrac\AstRunner\Resolver\AnnotationDependencyResolver;
use Qossmic\Deptrac\AstRunner\Resolver\AnonymousClassResolver;
use Qossmic\Deptrac\AstRunner\Resolver\ClassConstantResolver;
use Qossmic\Deptrac\AstRunner\Resolver\PropertyTypeResolver;
use Qossmic\Deptrac\AstRunner\Resolver\TypeResolver;
use Qossmic\Deptrac\Collector\BoolCollector;
use Qossmic\Deptrac\Collector\ClassNameCollector;
use Qossmic\Deptrac\Collector\ClassNameRegexCollector;
use Qossmic\Deptrac\Collector\DirectoryCollector;
use Qossmic\Deptrac\Collector\ExtendsCollector;
use Qossmic\Deptrac\Collector\ImplementsCollector;
use Qossmic\Deptrac\Collector\InheritanceLevelCollector;
use Qossmic\Deptrac\Collector\InheritsCollector;
use Qossmic\Deptrac\Collector\MethodCollector;
use Qossmic\Deptrac\Collector\Registry;
use Qossmic\Deptrac\Collector\UsesCollector;
use Qossmic\Deptrac\Configuration\Dumper;
use Qossmic\Deptrac\Configuration\Loader;
use Qossmic\Deptrac\Configuration\Loader\YmlFileLoader;
use Qossmic\Deptrac\Configuration\ParameterResolver;
use Qossmic\Deptrac\Console\Command\AnalyzeCommand;
use Qossmic\Deptrac\Console\Command\InitCommand;
use Qossmic\Deptrac\Dependency\InheritanceFlatter;
use Qossmic\Deptrac\Dependency\Resolver;
use Qossmic\Deptrac\DependencyEmitter\BasicDependencyEmitter;
use Qossmic\Deptrac\DependencyEmitter\InheritanceDependencyEmitter;
use Qossmic\Deptrac\FileResolver;
use Qossmic\Deptrac\OutputFormatter\BaselineOutputFormatter;
use Qossmic\Deptrac\OutputFormatter\ConsoleOutputFormatter;
use Qossmic\Deptrac\OutputFormatter\GithubActionsOutputFormatter;
use Qossmic\Deptrac\OutputFormatter\GraphVizOutputFormatter;
use Qossmic\Deptrac\OutputFormatter\JUnitOutputFormatter;
use Qossmic\Deptrac\OutputFormatter\TableOutputFormatter;
use Qossmic\Deptrac\OutputFormatter\XMLOutputFormatter;
use Qossmic\Deptrac\OutputFormatterFactory;
use Qossmic\Deptrac\RulesetEngine;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use Symfony\Component\EventDispatcher\EventDispatcher;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->private();

    $services->set(EventDispatcher::class);

    $services->set(AstRunner::class)
        ->args([
            service(EventDispatcher::class),
            service(NikicPhpParser::class),
        ]);

    $services->set(AstFileReferenceInMemoryCache::class);
    $services->alias(AstFileReferenceCache::class, AstFileReferenceInMemoryCache::class);

    $services
        ->set(Parser::class)
        ->factory([ParserFactory::class, 'createParser']);

    $services
        ->set(MemoizingParser::class)
        ->decorate(Parser::class)
        ->args([service('.inner')]);

    $services
        ->set(NikicPhpParser::class)
        ->args([
            service(Parser::class),
            service(AstFileReferenceCache::class),
            service(TypeResolver::class),
            service(AnnotationDependencyResolver::class),
            service(AnonymousClassResolver::class),
            service(ClassConstantResolver::class),
            service(PropertyTypeResolver::class),
        ]);

    $services
        ->set(AnnotationDependencyResolver::class)
        ->args([service(TypeResolver::class)]);
    $services->set(AnonymousClassResolver::class);
    $services->set(ClassConstantResolver::class);
    $services->set(TypeResolver::class);
    $services
        ->set(PropertyTypeResolver::class)
        ->args([service(TypeResolver::class)]);

    $services
        ->set(Analyser::class)
        ->args([
            service(AstRunner::class),
            service(FileResolver::class),
            service(Resolver::class),
            service(Registry::class),
            service(RulesetEngine::class),
            service(ParameterResolver::class),
        ]);

    $services->set(RulesetEngine::class);
    $services->set(FileResolver::class);

    /* Configuration */
    $services->set(YmlFileLoader::class);
    $services->set(Dumper::class);
    $services
        ->set(Loader::class)
        ->args([
            service(YmlFileLoader::class),
            param('currentWorkingDirectory'),
        ]);
    $services->set(ParameterResolver::class);

    /* Formatters */
    $services
        ->set(OutputFormatterFactory::class)
        ->args([tagged_iterator('output_formatter')]);
    $services
        ->set(ConsoleOutputFormatter::class)
        ->tag('output_formatter');
    $services
        ->set(GithubActionsOutputFormatter::class)
        ->tag('output_formatter');
    $services
        ->set(GraphVizOutputFormatter::class)
        ->tag('output_formatter');
    $services
        ->set(JUnitOutputFormatter::class)
        ->tag('output_formatter');
    $services
        ->set(TableOutputFormatter::class)
        ->tag('output_formatter');
    $services
        ->set(XMLOutputFormatter::class)
        ->tag('output_formatter');
    $services
        ->set(BaselineOutputFormatter::class)
        ->tag('output_formatter');

    /* Collectors */
    $services
        ->set(Registry::class)
        ->args([tagged_iterator('collector')]);
    $services
        ->set(BoolCollector::class)
        ->tag('collector');
    $services
        ->set(ClassNameCollector::class)
        ->tag('collector');
    $services
        ->set(ClassNameRegexCollector::class)
        ->tag('collector');
    $services
        ->set(DirectoryCollector::class)
        ->tag('collector');
    $services
        ->set(InheritanceLevelCollector::class)
        ->tag('collector');
    $services
        ->set(ImplementsCollector::class)
        ->tag('collector');
    $services
        ->set(ExtendsCollector::class)
        ->tag('collector');
    $services
        ->set(InheritsCollector::class)
        ->tag('collector');
    $services
        ->set(UsesCollector::class)
        ->tag('collector');
    $services
        ->set(MethodCollector::class)
        ->args([service(NikicPhpParser::class)])
        ->tag('collector');

    /* Dependency resolving */
    $services
        ->set(Resolver::class)
        ->args([
            service(EventDispatcher::class),
            service(InheritanceFlatter::class),
            tagged_iterator('dependency_emitter'),
        ]);
    $services->set(InheritanceFlatter::class);
    $services
        ->set(InheritanceDependencyEmitter::class)
        ->tag('dependency_emitter');
    $services
        ->set(BasicDependencyEmitter::class)
        ->tag('dependency_emitter');

    /* Commands */
    $services
        ->set(InitCommand::class)
        ->args([service(Dumper::class)])
        ->public();

    $services
        ->set(AnalyzeCommand::class)
        ->args([
            service(Analyser::class),
            service(Loader::class),
            service(EventDispatcher::class),
            service(OutputFormatterFactory::class),
        ])
        ->public();

    /* better reflection */
    $services
        ->set(Factory::class)
        ->args([service(Parser::class)]);

    $services
        ->set(BetterReflection::class)
        ->factory([Factory::class, 'create']);
};
