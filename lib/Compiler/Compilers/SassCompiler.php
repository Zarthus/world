<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use Zarthus\Sass\Sass;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Compiler\CompileResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\CompilerSupport;
use Zarthus\World\Compiler\CompileType;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class SassCompiler implements CompilerInterface
{
    use LogAwareTrait;

    private CompilerSupport $compilerSupport;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment,
        private readonly Sass $sassCompiler,
    ) {
        $this->compilerSupport = new CompilerSupport(['css', 'scss', 'sass'], ['scss', 'sass']);
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        if (!$this->environment->getBool(EnvVar::Sass)) {
            return false;
        }

        if (!$this->compilerSupport->supports($options, $template)) {
            return false;
        }

        if (null !== $template) {
            return file_exists($this->getTemplatePath($options->getInDirectory(), $template));
        }

        return true;
    }

    public function compile(CompilerOptions $options): void
    {
        if (!$this->supports($options, null)) {
            throw new CompilerException(self::class, "Unsupported instruction; " . $options->getInDirectory());
        }

        $this->sassCompiler->getApi()->compile($options->getInDirectory(), $options->getOutDirectory());
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        if (!$this->supports($options, $template)) {
            throw new TemplateNotFoundException($template, $options, self::class);
        }

        $this->sassCompiler->getApi()->compile(
            $this->getTemplatePath($options->getInDirectory(), $template),
            $this->getTemplatePath($options->getOutDirectory(), $template),
        );
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        if (!$this->supports($options, $template)) {
            throw new TemplateNotFoundException($template, $options, self::class);
        }

        return new CompileResult(CompileType::Css, $this->sassCompiler->getApi()->compile(
            $this->getTemplatePath($options->getInDirectory(), $template),
            $this->getTemplatePath($options->getOutDirectory(), $template),
        )->getCss());
    }

    private function getTemplatePath(string $path, string $template): string
    {
        $this->getLogger()->error("PATH=$path");
        return $path . '/' . $template;
    }
}
