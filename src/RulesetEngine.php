<?php

declare(strict_types=1);

namespace Qossmic\Deptrac;

use InvalidArgumentException;
use JetBrains\PHPStormStub\PhpStormStubsMap;
use Qossmic\Deptrac\AstRunner\AstMap\ClassLikeName;
use Qossmic\Deptrac\AstRunner\AstMap\TokenName;
use Qossmic\Deptrac\Configuration\Configuration;
use Qossmic\Deptrac\Dependency\Result;
use Qossmic\Deptrac\RulesetEngine\Allowed;
use Qossmic\Deptrac\RulesetEngine\Context;
use Qossmic\Deptrac\RulesetEngine\Error;
use Qossmic\Deptrac\RulesetEngine\SkippedViolation;
use Qossmic\Deptrac\RulesetEngine\SkippedViolationHelper;
use Qossmic\Deptrac\RulesetEngine\Uncovered;
use Qossmic\Deptrac\RulesetEngine\Violation;
use Qossmic\Deptrac\RulesetEngine\Warning;

class RulesetEngine
{
    public function process(
        Result $dependencyResult,
        TokenLayerResolverInterface $tokenLayerResolver,
        Configuration $configuration
    ): Context {
        $rules = [];
        $warnings = [];
        $errors = [];

        $configurationRuleset = $configuration->getRuleset();
        $skippedViolationHelper = new SkippedViolationHelper($configuration->getSkipViolations());

        foreach ($dependencyResult->getDependenciesAndInheritDependencies() as $dependency) {
            $dependant = $dependency->getDependant();
            $dependantLayerNames = $tokenLayerResolver->getLayersByTokenName($dependant);

            if (!isset($warnings[$dependant->toString()]) && count($dependantLayerNames) > 1) {
                $warnings[$dependant->toString()] = Warning::tokenIsInMoreThanOneLayer($dependant, $dependantLayerNames);
            }

            foreach ($dependantLayerNames as $dependantLayerName) {
                try {
                    $allowedDependencies = $configurationRuleset->getAllowedDependencies($dependantLayerName);
                } catch (InvalidArgumentException $exception) {
                    $errors[] = new Error($exception->getMessage());
                    continue;
                }

                $dependee = $dependency->getDependee();
                $dependeeLayerNames = $tokenLayerResolver->getLayersByTokenName($dependee);

                if (0 === count($dependeeLayerNames)) {
                    if ($dependee instanceof ClassLikeName && !$this->ignoreUncoveredInternalClass($configuration, $dependee)) {
                        $rules[] = new Uncovered($dependency, $dependantLayerName);
                    }
                    continue;
                }

                foreach ($dependeeLayerNames as $dependeeLayerName) {
                    if ($dependantLayerName === $dependeeLayerName) {
                        continue;
                    }

                    if (in_array($dependeeLayerName, $allowedDependencies, true)) {
                        $rules[] = new Allowed($dependency, $dependantLayerName, $dependeeLayerName);
                        continue;
                    }

                    if ($skippedViolationHelper->isViolationSkipped($dependant->toString(), $dependee->toString())) {
                        $rules[] = new SkippedViolation($dependency, $dependantLayerName, $dependeeLayerName);
                        continue;
                    }

                    $rules[] = new Violation($dependency, $dependantLayerName, $dependeeLayerName);
                }
            }
        }

        foreach ($skippedViolationHelper->unmatchedSkippedViolations() as $classLikeNameA => $classLikes) {
            foreach ($classLikes as $classLikeNameB) {
                $errors[] = new Error(sprintf('Skipped violation "%s" for "%s" was not matched.', $classLikeNameB, $classLikeNameA));
            }
        }

        return new Context($rules, $errors, $warnings);
    }

    private function ignoreUncoveredInternalClass(Configuration $configuration, TokenName $tokenName): bool
    {
        return !$tokenName instanceof ClassLikeName || ($configuration->ignoreUncoveredInternalClasses() && isset(PhpStormStubsMap::CLASSES[$tokenName->toString()]));
    }
}
