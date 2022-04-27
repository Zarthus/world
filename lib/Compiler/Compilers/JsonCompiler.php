<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Compiler\CompileResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\CompilerSupport;
use Zarthus\World\Compiler\CompileType;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Exception\TemplateIllegalException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class JsonCompiler implements CompilerInterface
{
    use LogAwareTrait;

    private readonly CompilerSupport $compilerSupport;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment,
        private readonly Filesystem $fs,
    ) {
        $this->compilerSupport = new CompilerSupport(['api'], ['json']);
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        return $this->compilerSupport->supports($options, $template);
    }

    public function compile(CompilerOptions $options): void
    {
        $finder = new Finder();
        $finder
            ->in($options->getInDirectory())
            ->ignoreDotFiles(true)
            ->notName('.twig')
            ->files();

        foreach ($finder as $file) {
            $this->compileTemplate($options, $file->getRelativePathname());
        }
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        $contents = $this->loadFile($options, $template);

        if (str_contains($template, '/')) {
            $dirname = dirname($template);
            if (!is_dir($dirname)) {
                $this->fs->mkdir($options->getOutDirectory() . $dirname);
            }
        }

        file_put_contents($options->getOutDirectory() . "/$template", $contents);
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        return new CompileResult(CompileType::Json, $this->loadFile($options, $template));
    }

    private function loadFile(CompilerOptions $options, string $template): string
    {
        if (!$this->supports($options, $template)) {
            throw new TemplateIllegalException($template, $this::class);
        }

        $file = $options->getInDirectory() . "/$template";
        if (is_dir($file)) {
            // We don't support directories as templates yet.
            throw new TemplateIllegalException($template, $this::class);
        }

        if (!str_ends_with($file, '.json')) {
            $file .= '.json';
        }

        if (!file_exists($file)) {
            throw new TemplateNotFoundException($template, $options, $this::class);
        }

        return file_get_contents($file);
    }
}
