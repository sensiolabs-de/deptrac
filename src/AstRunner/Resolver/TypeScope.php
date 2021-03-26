<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\Resolver;

class TypeScope
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var array<string, string> alias => className
     */
    private $uses;

    /**
     * @var array<string, string[]> name => types
     */
    private $variables;

    /**
     * @param array<string, string>   $uses      alias => className
     * @param array<string, string[]> $variables name => types
     */
    public function __construct(string $namespace, array $uses = [], array $variables = [])
    {
        $this->namespace = $namespace;
        $this->uses = $uses;
        $this->variables = $variables;
    }

    public function addUse(string $className, ?string $alias): void
    {
        $this->uses[$alias ?: $className] = $className;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return array<string, string>
     */
    public function getUses(): array
    {
        return $this->uses;
    }

    public function assignVariable(string $var, string ...$types): void
    {
        $this->variables[$var] = $types;
    }

    /**
     * @return string[]
     */
    public function getVariable(string $var): array
    {
        return $this->variables[$var] ?? [];
    }

    public function enterClassMethod(): self
    {
        return new self(
            $this->namespace,
            $this->uses,
            $this->variables
        );
    }
}
