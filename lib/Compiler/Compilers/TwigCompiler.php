<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use Symfony\Component\Finder\Finder;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error as TwigError;
use Twig\Extension\CoreExtension;
use Twig\Extension\DebugExtension;
use Twig\Extension\ProfilerExtension;
use Twig\Loader\FilesystemLoader as TwigFsLoader;
use Twig\Profiler\Profile;
use Twig\TwigFunction;
use Zarthus\World\App\App;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Compiler\CompileResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\CompilerSupport;
use Zarthus\World\Compiler\CompileType;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateIllegalException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class TwigCompiler implements CompilerInterface
{
    use LogAwareTrait;

    private readonly CompilerSupport $compilerSupport;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment,
    ) {
        $this->compilerSupport = new CompilerSupport(['api', 'html'], ['twig']);
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        if (!$this->compilerSupport->supports($options, $template)) {
            return false;
        }

        if (null === $template) {
            // Partial support is offered: We compile .json.twig files in `/api`,
            // Which means it only applies to cases where $template is non-null.
            if (str_contains($options->getInDirectory(), 'api')) {
                return true;
            }

            return true;
        }

        return $this->createEngine($options)->getLoader()->exists($template);
    }

    public function compile(CompilerOptions $options): void
    {
        if (!$this->compilerSupport->supports($options, null)) {
            throw new CompilerException($this::class, 'Compiler does not support this directory');
        }

        $engine = $this->createEngine($options);

        $finder = new Finder();
        $finder->in($options->getInDirectory());
        $finder->ignoreDotFiles(true);

        foreach ($finder as $fileInfo) {
            if ($fileInfo->isDir()) {
                $this->getLogger()->debug("Compiling Directory: {$fileInfo->getFilename()}");
                continue;
            }

            $path = str_replace($options->getInDirectory(), '', $fileInfo->getPath());
            $template = $this->normalizeTemplate($path . '/' . $fileInfo->getFilenameWithoutExtension(), $options->getInDirectory());
            $contents = $this->compileFile($engine, $options, $template);
            $fullPath = $this->createOutPath($options, $template);

            $this->write($fullPath, $contents);
        }
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        if (!$this->compilerSupport->supports($options, $template)) {
            throw new TemplateIllegalException($template, $this::class);
        }

        $contents = $this->renderTemplate($options, $template);
        $fullPath = $this->createOutPath($options, $template);

        $this->write($fullPath, (string) $contents);
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        $template = $this->normalizeTemplate($template, $options->getInDirectory());
        if (!$this->compilerSupport->supports($options, $template)) {
            throw new TemplateIllegalException($template, $this::class);
        }

        $engine = $this->createEngine($options);

        return new CompileResult(
            CompileType::Twig,
            $this->compileFile($engine, $options, $template),
            $this->resolveMimeType($template, $options),
        );
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
            throw new TemplateNotFoundException($template, $options, $this::class);
        }

        try {
            $compiled = $engine->render($template);
        } catch (TwigError $e) {
            $this->getLogger()->critical(implode("\n", [
                "Compilation of $template failed due to a compiler error: ",
                $e->getMessage() . ' - ' . $e->getSourceContext()?->getName(),
            ]), ['exception' => $e]);
            throw new CompilerException($this::class, $e->getMessage(), previous: $e);
        } catch (\Exception $e) {
            $this->getLogger()->critical(
                "Compilation of $template failed due to an Exception being thrown. (" . $e::class . " . {$e->getMessage()}",
                ['exception' => $e]
            );
            throw new CompilerException($this::class, $e->getMessage(), previous: $e);
        } catch (\Throwable $throwable) {
            $this->getLogger()->critical(
                "Compilation of $template failed due to an error in the template. (" . $throwable::class . " . {$throwable->getMessage()}",
                ['exception' => $throwable]
            );
            throw new CompilerException($this::class, $throwable::class . ' ' . $throwable->getMessage(), previous: $throwable);
        }

        return $compiled;
    }

    private function createEngine(CompilerOptions $options): TwigEnvironment
    {
        $loader = new TwigFsLoader([$options->getInDirectory()], $options->getInDirectory());
        $twig = new TwigEnvironment($loader, [
            'cache' => $this->environment->getBool(EnvVar::Development) ? false : Path::tmp() . '/twig',
            'debug' => $this->environment->getBool(EnvVar::Development),
            'strict_variables' => true,
            'optimizations' => $this->environment->getBool(EnvVar::Development) ? 0 : -1,
            'autoescape' => 'html',
        ]);

        // Assigns global variables to all templates.
        $twig->addGlobal('appName', App::name());
        $twig->addGlobal('appVersion', App::version());
        $twig->addGlobal('environment', $this->environment->getString(EnvVar::Name));
        $twig->addGlobal('development', $this->environment->getBool(EnvVar::Development));
        $twig->addGlobal('language', 'en');

        /** @var CoreExtension $core */
        $core = $twig->getExtension(CoreExtension::class);
        $core->setDateFormat(\DateTimeInterface::ATOM);
        $core->setTimezone(new \DateTimeZone('UTC'));

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

    private function createOutPath(
        CompilerOptions $options,
        string $template,
        ?string $appendExtension = null
    ): string {
        if (null === $appendExtension) {
            // there is already an extension (e.g. .json.twig, .html.twig)
            if (preg_match('@\.[a-z\d]+$@', str_replace('.twig', '', $template))) {
                $appendExtension = '';
            }

            $appendExtension ??= '.html';
        }

        $normalizedTemplate = str_replace('.twig', $appendExtension, $template);
        return $options->getOutDirectory() . '/' .  $normalizedTemplate;
    }

    private function normalizeTemplate(string $template, string $path): string
    {
        if ('/' === $template || str_ends_with($template, '/')) {
            $template .= 'index';
        }
        if (str_starts_with($template, '/')) {
            $template = ltrim($template, '/');
        }

        $tryFiles = [$template, $template . '.twig'];
        foreach ($tryFiles as $file) {
            if (file_exists($path . '/' . $file)) {
                return $file;
            }
        }

        return trim($template) . '.twig';
    }

    private function write(string $fullPath, string $contents): void
    {
        $dir = dirname($fullPath);
        if (!is_dir($dir) && !mkdir($dir, recursive: true) && !is_dir($dir)) {
            throw new CompilerException($this::class, 'Cannot create directory: ' . $dir);
        }
        file_put_contents($fullPath, $contents);
    }

    private function resolveMimeType(string $template, CompilerOptions $options): ?string
    {
        if (str_ends_with($options->getInDirectory(), '/html')) {
            $mimeType = 'text/html';
        } else {
            $mimeType = null;
            $tryMimeTypes = [
                $options->getOutDirectory() . '/' . $template,
                $options->getInDirectory() . '/' . $template,
            ];
            foreach ($tryMimeTypes as $path) {
                if (file_exists($path)) {
                    $mimeType = mime_content_type($path);
                    $this->getLogger()->debug("Determined MimeType of $path to be $mimeType");
                }
            }
        }

        return empty($mimeType) ? null : $mimeType;
    }
}
