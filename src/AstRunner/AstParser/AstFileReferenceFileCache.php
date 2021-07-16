<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstParser;

use Qossmic\Deptrac\AstRunner\AstMap\AstClassReference;
use Qossmic\Deptrac\AstRunner\AstMap\AstDependency;
use Qossmic\Deptrac\AstRunner\AstMap\AstFileReference;
use Qossmic\Deptrac\AstRunner\AstMap\AstInherit;
use Qossmic\Deptrac\AstRunner\AstMap\ClassLikeName;
use Qossmic\Deptrac\AstRunner\AstMap\FileOccurrence;
use Qossmic\Deptrac\Console\Application;
use Qossmic\Deptrac\File\FileReader;

class AstFileReferenceFileCache implements AstFileReferenceCache
{
    /** @var array<string, array{hash: string, reference: AstFileReference}> */
    private array $cache;
    private string $cacheFile;
    private bool $loaded = false;
    /** @var array<string, bool> */
    private array $parsedFiles = [];

    public function __construct(string $cacheFile)
    {
        $this->cache = [];
        $this->cacheFile = $cacheFile;
    }

    public function has(string $filepath): bool
    {
        $this->load();

        $filepath = $this->normalizeFilepath($filepath);

        if (!isset($this->cache[$filepath])) {
            return false;
        }

        $hash = sha1_file($filepath);

        if ($hash !== $this->cache[$filepath]['hash']) {
            unset($this->cache[$filepath]);

            return false;
        }

        return true;
    }

    public function get(string $filepath): ?AstFileReference
    {
        $this->load();

        $filepath = $this->normalizeFilepath($filepath);

        if ($this->has($filepath)) {
            $this->parsedFiles[$filepath] = true;

            return $this->cache[$filepath]['reference'];
        }

        return null;
    }

    public function set(AstFileReference $fileReference): void
    {
        $this->load();

        $filepath = $this->normalizeFilepath($fileReference->getFilepath());

        $this->parsedFiles[$filepath] = true;

        $this->cache[$filepath] = [
            'hash' => (string) sha1_file($filepath),
            'reference' => $fileReference,
        ];
    }

    public function load(): void
    {
        if (true === $this->loaded) {
            return;
        }

        if (!file_exists($this->cacheFile) || !is_readable($this->cacheFile)) {
            return;
        }

        $contents = FileReader::read($this->cacheFile);

        /** @var array{version: string, payload: array<string, array{hash: string, reference: string}>} $cache */
        $cache = json_decode($contents, true);

        $this->loaded = true;

        if (Application::VERSION !== $cache['version']) {
            return;
        }

        /** @var array<string, array{hash: string, reference: AstFileReference}> $deserialized */
        $deserialized = array_map(
            /** @param array{hash: string, reference: string} $data */
            static function (array $data): array {
                $reference = unserialize(
                    $data['reference'],
                    [
                        'allowed_classes' => [
                            //TODO: Add new classes (Patrick Kusebauch @ 10.07.21)
                            AstFileReference::class,
                            AstClassReference::class,
                            AstInherit::class,
                            AstDependency::class,
                            ClassLikeName::class,
                            FileOccurrence::class,
                        ],
                    ]
                );
                assert($reference instanceof AstFileReference);

                return [
                    'hash' => $data['hash'],
                    'reference' => $reference,
                ];
            },
            $cache['payload']
        );

        $this->cache = $deserialized;
    }

    public function write(): void
    {
        if (!is_writable(\dirname($this->cacheFile))) {
            return;
        }

        $cache = array_filter(
            $this->cache,
            function (string $key): bool {
                return isset($this->parsedFiles[$key]);
            },
            ARRAY_FILTER_USE_KEY
        );

        $payload = array_map(
            static function (array $data): array {
                $data['reference'] = serialize($data['reference']);

                return $data;
            },
            $cache
        );

        file_put_contents(
            $this->cacheFile,
            json_encode(
                [
                    'version' => Application::VERSION,
                    'payload' => $payload,
                ]
            )
        );
    }

    private function normalizeFilepath(string $filepath): string
    {
        $normalized = realpath($filepath);

        if (false === $normalized) {
            throw new FileNotExistsException($filepath);
        }

        return $normalized;
    }
}
