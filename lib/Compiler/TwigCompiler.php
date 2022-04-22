<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

use Symfony\Component\Finder\Finder;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error as TwigError;
use Twig\Extension\DebugExtension;
use Twig\Extension\ProfilerExtension;
use Twig\Loader\FilesystemLoader as TwigFsLoader;
use Twig\Profiler\Profile;
use Twig\TwigFunction;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateIllegalException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class TwigCompiler implements CompilerInterface
{
    use LogAwareTrait;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment
    ) {
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        try {
            $this->validate($options);
        } catch (CompilerException) {
            return false;
        }

        if (null !== $template) {
            return $this->createEngine($options)->getLoader()->exists($template);
        }
        return false;
    }

    public function compile(CompilerOptions $options): void
    {
        $this->validate($options);
        $engine = $this->createEngine($options);

        $finder = new Finder();
        $finder->in($options->getInDirectory());
        $finder->ignoreDotFiles(true);

        foreach ($finder as $fileInfo) {
            if ($fileInfo->isDir()) {
                $this->getLogger()->info("Directory: {$fileInfo->getFilename()}");
                continue;
            }

            $path = str_replace($options->getInDirectory(), '', $fileInfo->getPath());
            $template = $this->normalizeTemplate($path . '/' . $fileInfo->getFilenameWithoutExtension());
            $contents = $this->compileFile($engine, $options, $template);
            $fullPath = $this->createOutPath($options, $template);

            $this->write($fullPath, $contents);
        }
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        $contents = $this->renderTemplate($options, $template);
        $fullPath = $this->createOutPath($options, $template);

        $this->write($fullPath, (string) $contents);
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        $this->validate($options);
        $engine = $this->createEngine($options);

        $template = $this->normalizeTemplate($template);

        return new CompileResult(CompileType::Html, $this->compileFile($engine, $options, $template));
    }

    /**
     * @param string $template A normalized template, otherwise security errors might occur! (directory traversal)
     *
     * @throws \Throwable Any {@see \Error}
     * @throws CompilerException
     */
    private function compileFile(TwigEnvironment $engine, CompilerOptions $options, string $template): string
    {
        $this->getLogger()->debug("Compiling '$template'");

        if (!$engine->getLoader()->exists($template)) {
            $this->getLogger()->debug("File $template does not exist.");
            throw new TemplateNotFoundException($template, $options);
        }

        try {
            $compiled = $engine->render($template);
        } catch (TwigError $e) {
            $this->getLogger()->critical("Compilation of $template failed due to a compiler error.");
            throw new CompilerException($e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            $this->getLogger()->critical("Compilation of $template failed due to an Exception being thrown.");
            throw new CompilerException($e->getMessage(), 0, $e);
        } catch (\Throwable $throwable) {
            $this->getLogger()->critical("Compilation of $template failed due to an error in the template.");
            throw $throwable;
        }

        return $compiled;
    }

    private function createEngine(CompilerOptions $options): TwigEnvironment
    {
        $loader = new TwigFsLoader([$options->getInDirectory()], $options->getInDirectory());
        $twig = new TwigEnvironment($loader, [
            'cache' => Path::tmp() . '/twig',
            'debug' => $this->environment->getBool(EnvVar::Development),
            'strict_variables' => true,
            'optimizations' => $this->environment->getBool(EnvVar::Development) ? 0 : -1,
        ]);

        // Assigns global variables to all templates.
        $twig->addGlobal('environment', $this->environment->getString(EnvVar::Name));
        $twig->addGlobal('development', $this->environment->getBool(EnvVar::Development));
        $twig->addGlobal('language', 'en');

        if ($this->environment->getBool(EnvVar::Development)) {
            $twig->addExtension(new DebugExtension());
        }
        if ($options->isLiveCompilation()) {
            $twig->addExtension(new ProfilerExtension(new Profile()));
        }

        /** @var array<string, callable> $functions */
        $functions = [];
        foreach ($functions as $name => $fn) {
            $twig->addFunction(new TwigFunction($name, $fn));
        }

        return $twig;
    }

    private function validate(CompilerOptions $options): void
    {
        [$in, $out] = [
            $options->getInDirectory(),
            $options->getOutDirectory(),
        ];

        if (!is_dir($in)) {
            throw new CompilerException("Directory '$in' does not exist.");
        }

        if (!is_dir($out)) {
            throw new CompilerException("Directory '$out' does not exist.");
        }

        if (__DIR__ === $in || __DIR__ === $out) {
            throw new CompilerException("Cannot target current directory");
        }
    }

    private function createOutPath(
        CompilerOptions $options,
        string $template,
        string $appendExtension = '.html'
    ): string {
        return $options->getOutDirectory()
            . '/'
            . mb_strtolower(str_replace('.twig', '', $template))
            . $appendExtension;
    }

    private function normalizeTemplate(string $template): string
    {
        if (str_ends_with($template, '/')) {
            $template .= 'index';
        }
        if (str_starts_with($template, '/')) {
            $template = ltrim($template, '/');
        }

        if (str_contains($template, '.')) {
            if (str_contains($template, '..')) {
                throw new TemplateIllegalException($template);
            }

            // support resources or files that don't need compilation, e.g. favicon.ico, script.js
            return $template;
        }

        return trim($template) . '.twig';
    }

    private function write(string $fullPath, string $contents): void
    {
        $dir = dirname($fullPath);
        if (!is_dir($dir) && !mkdir($dir, recursive: true) && !is_dir($dir)) {
            throw new CompilerException('Cannot create directory: ' . $dir);
        }
        file_put_contents($fullPath, $contents);
    }
}
