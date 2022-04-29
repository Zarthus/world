<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use Zarthus\World\File\MimeTypeResolverInterface;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Compiler\CompileResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\CompilerSupport;
use Zarthus\World\Compiler\CompileType;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class DebugCompiler implements CompilerInterface
{
    use LogAwareTrait;

    private readonly CompilerSupport $compilerSupport;

    public function __construct(
        private readonly MimeTypeResolverInterface $mimeTypeResolver,
        private readonly Environment $environment,
    ) {
        $this->compilerSupport = new CompilerSupport(['vendor'], ['js', 'css'], true);
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        return $options->isLiveCompilation() &&
            $this->environment->getBool(EnvVar::Development) &&
            null !== $template &&
            $this->compilerSupport->supports($options, $template) &&
            str_ends_with($options->getInDirectory(), 'vendor');
    }

    public function compile(CompilerOptions $options): void
    {
        if (!$this->compilerSupport->supports($options, null)) {
            throw new CompilerException($this::class, 'Compiler does not support this directory');
        }
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        if (!$this->compilerSupport->supports($options, null)) {
            throw new CompilerException($this::class, 'Compiler does not support this directory');
        }
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        if (!$this->compilerSupport->supports($options, null)) {
            throw new CompilerException($this::class, 'Compiler does not support this directory');
        }

        $path = Path::root() . '/vendor/' . $template;

        if (!file_exists($path)) {
            throw new TemplateNotFoundException($template, $options, self::class);
        }

        return new CompileResult(CompileType::Asset, file_get_contents($path), $this->mimeTypeResolver->resolve($path));
    }
}
