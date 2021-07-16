<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\AstRunner\AstMap;

class FunctionReferenceBuilder extends ReferenceBuilder
{
    private string $functionName;

    protected function __construct(string $filepath, string $functionName)
    {
        //TODO: Function templates
        parent::__construct([], $filepath);
        $this->functionName = $functionName;
    }

    public static function create(string $filepath, string $functionName): self
    {
        return new self($filepath, $functionName);
    }

    /** @internal */
    public function build(): AstFunctionReference
    {
        return new AstFunctionReference(
            FunctionName::fromFQCN($this->functionName),
            $this->dependencies
        );
    }
}
