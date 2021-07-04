<?php

declare(strict_types=1);

namespace Qossmic\Deptrac;

use InvalidArgumentException;
use JetBrains\PHPStormStub\PhpStormStubsMap;
use Qossmic\Deptrac\AstRunner\AstMap\ClassLikeName;
use Qossmic\Deptrac\Configuration\ConfigurationRuleset;
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
        ClassLikeLayerResolverInterface $classLikeLayerResolver,
        ConfigurationRuleset $configurationRuleset
    ): Context {
        $rules = [];
        $warnings = [];
        $errors = [];

        $skippedViolationHelper = new SkippedViolationHelper($configurationRuleset->getSkipViolations());

        foreach ($dependencyResult->getDependenciesAndInheritDependencies() as $dependency) {
            $layerNames = $classLikeLayerResolver->getLayersByClassLikeName($dependency->getClassLikeNameA());

            $classLikeANameString = $dependency->getClassLikeNameA()->toString();
            if (!isset($warnings[$classLikeANameString]) && count($layerNames) > 1) {
                $warnings[$classLikeANameString] = Warning::classLikeIsInMoreThanOneLayer($dependency->getClassLikeNameA(), $layerNames);
            }

            foreach ($layerNames as $layerName) {
                try {
                    $allowedDependencies = $configurationRuleset->getAllowedDependencies($layerName);
                } catch (InvalidArgumentException $exception) {
                    $errors[] = new Error($exception->getMessage());
                    continue;
                }

                $layersNamesClassB = $classLikeLayerResolver->getLayersByClassLikeName($dependency->getClassLikeNameB());

                if (0 === count($layersNamesClassB)) {
                    if (!$this->ignoreUncoveredInternalClass($configurationRuleset, $dependency->getClassLikeNameB())) {
                        $rules[] = new Uncovered($dependency, $layerName);
                    }
                    continue;
                }

                foreach ($layersNamesClassB as $layerNameOfDependency) {
                    if ($layerName === $layerNameOfDependency) {
                        continue;
                    }

                    if (in_array($layerNameOfDependency, $allowedDependencies, true)) {
                        $rules[] = new Allowed($dependency, $layerName, $layerNameOfDependency);
                        continue;
                    }

                    if ($skippedViolationHelper->isViolationSkipped($dependency->getClassLikeNameA(), $dependency->getClassLikeNameB())) {
                        $rules[] = new SkippedViolation($dependency, $layerName, $layerNameOfDependency);
                        continue;
                    }

                    $rules[] = new Violation($dependency, $layerName, $layerNameOfDependency);
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

    private function ignoreUncoveredInternalClass(ConfigurationRuleset $configurationRuleset, ClassLikeName $classLikeName): bool
    {
        return $configurationRuleset->ignoreUncoveredInternalClasses() && isset(PhpStormStubsMap::CLASSES[$classLikeName->toString()]);
    }
}
