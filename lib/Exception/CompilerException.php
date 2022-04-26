<?php

declare(strict_types=1);

namespace Zarthus\World\Exception;

use Symfony\Component\String\UnicodeString;
use Zarthus\World\Compiler\CompilerInterface;

class CompilerException extends AppException
{
    /**
     * @param null|class-string<CompilerInterface> $compiler
     */
    public function __construct(
        private readonly ?string $compiler,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ($this->formatCompilerClassString($this->compiler) ?? '') . $message,
            $code,
            $previous
        );
    }

    public function getCompiler(): ?string
    {
        return $this->compiler;
    }

    protected function formatCompilerClassString(?string $compiler): ?string
    {
        if ($compiler === null) {
            return null;
        }

        return (new UnicodeString("$compiler (L{$this->line}): "))
            ->replace('Zarthus\World\Compiler\\Compilers\\', '')
            ->toString();
    }
}
