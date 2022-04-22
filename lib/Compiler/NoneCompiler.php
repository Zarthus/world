<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Exception\CompilerException;

final class NoneCompiler implements CompilerInterface
{
    use LogAwareTrait;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment
    ) {
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        return null !== $template
            && !str_contains($template, '..')
            && file_exists($options->getOutDirectory() . '/' . $template);
    }

    public function compile(CompilerOptions $options): void
    {
        throw new CompilerException("NoneCompiler does not support compiling directories.");
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        // no-op
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        return new CompileResult(
            CompileType::Plain,
            file_get_contents($file = $options->getOutDirectory() . '/' . $template),
            ($mimeType = mime_content_type($file)) === false ? null : $mimeType,
        );
    }
}
