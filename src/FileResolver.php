<?php

declare(strict_types=1);

namespace Qossmic\Deptrac;

use Iterator;
use Qossmic\Deptrac\Configuration\Configuration;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class FileResolver
{
    /**
     * @return string[]
     */
    public function resolve(Configuration $configuration): array
    {
        try {
            $finder = (new Finder())
                ->in($configuration->getPaths())
                ->name('*.php')
                ->files()
                ->followLinks()
                ->ignoreUnreadableDirs(true)
                ->ignoreVCS(true)
                ->notPath($configuration->getExcludeFiles());
        } catch (DirectoryNotFoundException $exception) {
            throw new \RuntimeException('Invalid directory provided for `paths`. '.$exception->getMessage());
        }

        $customFilterIterator = $finder->getIterator();

        if (!$customFilterIterator instanceof Iterator) {
            throw new RuntimeException('unable to create an iterator for the configured paths');
        }

        $finder = new PathNameFilterIterator($customFilterIterator, [], $configuration->getExcludeFiles());

        return array_map(
            static function (SplFileInfo $fileInfo) {
                return (string) $fileInfo->getRealPath();
            },
            iterator_to_array($finder)
        );
    }
}
