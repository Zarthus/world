<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

use Zarthus\World\Exception\CompilerException;

interface CompilerInterface
{
    public function supports(CompilerOptions $options, ?string $template): bool;

    /**
     * Compiles all templates in the in-directory
     *
     * @throws CompilerException
     */
    public function compile(CompilerOptions $options): void;

    /**
     * @param string $template the path to the template, relative from the in-directory, including name, excluding extension.
     *
     * @throws CompilerException
     */
    public function compileTemplate(CompilerOptions $options, string $template): void;

    /**
     * @param string $template the path to the template, relative from the in-directory, including name, excluding extension.
     *
     * @throws CompilerException
     */
    public function renderTemplate(CompilerOptions $options, string $template): CompileResult;
}
