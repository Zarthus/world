<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use Symfony\Component\Finder\Finder;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error as TwigError;
use Twig\Extension\CoreExtension;
use Twig\Extension\DebugExtension;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;
use Zarthus\World\App\App;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Compiler\CompileResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\CompilerSupport;
use Zarthus\World\Compiler\CompileType;
use Zarthus\World\Compiler\Twig\Extension\TwigExtensionProviderInterface;
use Zarthus\World\Compiler\Twig\TwigUniqueFilesystemLoader;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateIllegalException;
use Zarthus\World\Exception\TemplateNotFoundException;
use Zarthus\World\File\MimeTypeResolverInterface;

final class TwigCompiler implements CompilerInterface
{
    use LogAwareTrait;

    private readonly CompilerSupport $compilerSupport;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment,
        private readonly MimeTypeResolverInterface $mimeTypeResolver,
        private readonly TwigExtensionProviderInterface $extensionProvider,
    ) {
        $this->compilerSupport = new CompilerSupport(['api', 'html'], ['twig', 'html']);
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        if (!$this->compilerSupport->supports($options, $template)) {
            // Emulate `/` mapping to `/index.html`
            // Does not work for mapping `/foo` to `/foo/index.html`, a trailing slash is required.
            if ($options->isLiveCompilation() &&
                (null !== $template && str_ends_with($template, '/'))
            ) {
                return true;
            }

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

        $engine = $this->createEngine($options);
        return $engine->getLoader()->exists($this->normalizeTemplate($template, $options->getInDirectory()));
    }

    public function compile(CompilerOptions $options): void
    {
        if (!$this->compilerSupport->supports($options, null)) {
            throw new CompilerException($this::class, 'Compiler does not support this directory');
        }

        $engine = $this->createEngine($options);

        $finder = new Finder();
        $finder->in($options->getInDirectory())
            ->ignoreDotFiles(true)
            ->exclude('_components')
            ->exclude('_layouts')
            ->exclude('_partials');

        foreach ($finder as $fileInfo) {
            if ($fileInfo->isDir()) {
                $this->getLogger()->debug("Compiling Directory: {$fileInfo->getFilename()}");
                continue;
            }

            $template = $this->normalizeTemplate($fileInfo->getRelativePathname(), $options->getInDirectory());
            $compiled = $this->compileFile($engine, $options, $template);
            $fullPath = $this->createOutPath($options, $template);

            $this->write($fullPath, $compiled);
        }
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        $compileResult = $this->renderTemplate($options, $template);
        $fullPath = $this->createOutPath($options, $template);

        $this->write($fullPath, $compileResult->getResult());
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        $template = $this->normalizeTemplate($template, $options->getInDirectory());

        if (!$this->compilerSupport->supports($options, $template)) {
            throw new TemplateIllegalException($template, $this::class);
        }

        return new CompileResult(
            CompileType::Twig,
            $this->compileFile($this->createEngine($options), $options, $template),
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
            $compiled = $engine->render($template, []);
        } catch (TwigError $e) {
            $this->getLogger()->critical(implode("\n", [
                "Compilation of $template failed due to a compiler error: ",
                $e->getMessage() . ' - ' . ($e->getSourceContext()?->getName() ?? '(sourcecontext)'),
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
        $loader = new TwigUniqueFilesystemLoader([$options->getInDirectory()], $options->getInDirectory());
        $twig = new TwigEnvironment($loader, [
            'cache' => $this->environment->getBool(EnvVar::Development) ? false : Path::tmp() . '/twig',
            'debug' => $this->environment->getBool(EnvVar::Development),
            'strict_variables' => true,
            'optimizations' => $this->environment->getBool(EnvVar::Development) ? 0 : -1,
            'autoescape' => 'html',
            'auto_reload' => $this->environment->getBool(EnvVar::Development) || $options->isLiveCompilation(),
        ]);

        // Assigns global variables to all templates.
        $twig->addGlobal('appName', App::name());
        $twig->addGlobal('appVersion', App::version());
        $twig->addGlobal('environment', $this->environment->getString(EnvVar::Name));
        $twig->addGlobal('development', $this->environment->getBool(EnvVar::Development));
        $twig->addGlobal('basedir', $this->environment->getString(EnvVar::HttpBaseDir));
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

        foreach ($this->extensionProvider->getExtensions() as $extension) {
            $twig->addExtension($extension);
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
        $template = str_replace('.html', '.twig', $template);

        if (str_starts_with($template, '/')) {
            $template = ltrim($template, '/');
        }

        $tryFiles = [$template, $template . '.twig', $template . '/index.twig'];
        foreach ($tryFiles as $file) {
            if (!is_dir("$path/$file") && file_exists("$path/$file")) {
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
            $replacement = str_replace('.twig', '', $template);

            $mimeType = null;
            $tryMimeTypes = [
                $options->getOutDirectory() . '/' . $replacement,
                $options->getOutDirectory() . '/' . $template,
                $options->getInDirectory() . '/' . $template,
            ];
            foreach ($tryMimeTypes as $path) {
                if (file_exists($path)) {
                    $mimeType = $this->mimeTypeResolver->resolve($path);
                    $relativePath = str_replace(Path::root(), '', $path);
                    $this->getLogger()->debug("Determined MimeType of $template to be $mimeType ($relativePath)");
                    break;
                }
            }
        }

        return empty($mimeType) ? null : $mimeType;
    }
}
