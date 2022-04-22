<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class JsonCompiler implements CompilerInterface
{
    use LogAwareTrait;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment
    ) {
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        return str_contains($options->getOutDirectory(), 'api/') ||
            str_contains($options->getInDirectory(), 'api/') ||
            (
                null !== $template &&
                !str_contains($template, '..') &&
                str_ends_with($template, '.json')
            );
    }

    public function compile(CompilerOptions $options): void
    {
        throw new CompilerException("NoneCompiler does not support compiling directories.");
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        // maybe in the future we want to offer dynamic APIs by actually having it backed by a PHP file or something
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        return new CompileResult(CompileType::Json, $this->loadFile($options, $template));
    }

    private function loadFile(CompilerOptions $options, string $template): string
    {
        $file = $options->getInDirectory() . "/$template";
        if (!str_ends_with($file, '.json')) {
            $file .= '.json';
        }

        if (!file_exists($file)) {
            throw new TemplateNotFoundException($template, $options);
        }

        return file_get_contents($file);
    }
}
