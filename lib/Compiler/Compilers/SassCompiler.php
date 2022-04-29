<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use Zarthus\Sass\Cli\V1\Options\SassCliOptions;
use Zarthus\Sass\Cli\V1\Options\SassStyle;
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
use Zarthus\World\File\MimeTypeResolver;

final class SassCompiler implements CompilerInterface
{
    use LogAwareTrait;

    private CompilerSupport $compilerSupport;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment,
        private readonly Sass $sassCompiler,
        private readonly MimeTypeResolver $mimeTypeResolver,
    ) {
        $this->compilerSupport = new CompilerSupport(['style'], ['scss', 'sass', 'css', 'map']);
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        if (!$this->environment->getBool(EnvVar::Sass)) {
            return false;
        }

        return $this->compilerSupport->supports($options, $template);
    }

    public function compile(CompilerOptions $options): void
    {
        if (!$this->supports($options, null)) {
            throw new CompilerException(self::class, "Unsupported instruction; " . $options->getInDirectory());
        }

        $this->sassCompiler->getApi()->compile($options->getInDirectory(), $options->getOutDirectory(), $this->createCliOptions());
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        if (!$this->supports($options, $template)) {
            throw new TemplateNotFoundException($template, $options, self::class);
        }

        $this->renderTemplate($options, $template);
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        if (!$this->supports($options, $template)) {
            throw new TemplateNotFoundException($template, $options, self::class);
        }

        $this->compile($options);
        $path = $this->getTemplatePath($options->getOutDirectory(), $template);

        return new CompileResult(
            CompileType::Css,
            file_get_contents($path),
            $this->mimeTypeResolver->resolve($path),
        );
    }

    private function getTemplatePath(string $path, string $template): string
    {
        return $path . '/' . $template;
    }

    private function createCliOptions(): ?SassCliOptions
    {
        if ($this->environment->getBool(EnvVar::Compress)) {
            return (new SassCliOptions())->withStyle(SassStyle::Compressed);
        }
        return null;
    }
}
