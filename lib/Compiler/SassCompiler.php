<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

use Zarthus\Sass\Sass;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class SassCompiler implements CompilerInterface
{
    use LogAwareTrait;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment,
        private readonly Sass $sassCompiler,
    ) {
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        if (!$this->environment->getBool(EnvVar::Sass)) {
            return false;
        }

        return str_contains($options->getOutDirectory(), 'css/') ||
            str_contains($options->getInDirectory(), 'sass/') ||
            str_contains($options->getInDirectory(), 'scss/') ||
            (
                null !== $template &&
                !str_contains($template, '..') &&
                str_ends_with($template, '.scss') &&
                str_ends_with($template, '.sass')
            );
    }

    public function compile(CompilerOptions $options): void
    {
        $this->validate($options);

        $this->sassCompiler->getApi()->compile($options->getInDirectory(), $options->getOutDirectory());
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        $in = $this->validateTemplate($options, $template);

        $this->sassCompiler->getApi()->compile(
            $in,
            $options->getOutDirectory() . '/' . $template
        );
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        $in = $this->validateTemplate($options, $template);

        return new CompileResult(CompileType::Css, $this->sassCompiler->getApi()->compile(
            $in,
            $options->getOutDirectory() . '/' . $template
        )->getCss());
    }

    private function validate(CompilerOptions $options): void
    {
        if (!is_dir($options->getInDirectory())) {
            throw new CompilerException("Directory in does not exist ({$options->getInDirectory()})");
        }
    }

    private function validateTemplate(CompilerOptions $options, string $template): string
    {
        $this->validate($options);
        $in = $options->getInDirectory() . '/' . $template;

        if (!file_exists($in)) {
            throw new TemplateNotFoundException($template, $options);
        }

        return $in;
    }
}
