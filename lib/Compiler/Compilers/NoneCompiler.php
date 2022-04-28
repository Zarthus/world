<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Compiler\CompileResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\CompileType;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateNotFoundException;

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
            && !is_dir($options->getOutDirectory() . '/' . $template)
            && file_exists($options->getOutDirectory() . '/' . $template);
    }

    public function compile(CompilerOptions $options): void
    {
        throw new CompilerException($this::class, 'This compiler does not support compiling directories.');
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        throw new TemplateNotFoundException($template, $options, $this::class);
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
