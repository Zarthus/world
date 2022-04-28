<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use League\CommonMark\CommonMarkConverter;
use Symfony\Component\Finder\Finder;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Compiler\CompileResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\CompilerSupport;
use Zarthus\World\Compiler\CompileType;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateNotFoundException;
use Zarthus\World\File\MimeTypeResolver;

final class MarkdownCompiler implements CompilerInterface
{
    use LogAwareTrait;

    private readonly CompilerSupport $compilerSupport;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment,
        private readonly MimeTypeResolver $mimeTypeResolver,
    ) {
        $this->compilerSupport = new CompilerSupport(['articles'], ['md']);
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        return $this->compilerSupport->supports($options, $template);
    }

    public function compile(CompilerOptions $options): void
    {
        $finder = new Finder();
        $finder->in($options->getInDirectory());
        $finder->ignoreDotFiles(true);

        foreach ($finder as $fileInfo) {
            if ($fileInfo->isDir()) {
                $this->getLogger()->info("Directory: {$fileInfo->getFilename()}");
                continue;
            }

            $template = $fileInfo->getRelativePathname();
            $this->compileTemplate($options, $template);
        }
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        $this->getLogger()->debug('Compiling: ' . $template);

        ['in' => $in, 'out' => $out] = $this->validate($options, $template);
        $engine = $this->createEngine();

        $input = file_get_contents($in);
        $output = $engine->convert($input);
        file_put_contents($out, $output->getContent());
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        ['in' => $in] = $this->validate($options, $template);
        $engine = $this->createEngine();

        $input = file_get_contents($in);
        return new CompileResult(
            CompileType::Twig,
            $engine->convert($input)->getContent(),
            $this->mimeTypeResolver->resolve($in),
        );
    }


    private function createEngine(): CommonMarkConverter
    {
        return new CommonMarkConverter();
    }

    /**
     * @return array{in:string, out:string}
     *
     * @throws CompilerException
     */
    private function validate(CompilerOptions $options, string $template): array
    {
        [$in, $out] = [
            $options->getInDirectory() . "/$template",
            $options->getOutDirectory() . "/" . str_replace('.md', '.html', $template),
        ];

        if (!file_exists($in)) {
            throw new TemplateNotFoundException($template, $options, $this::class);
        }

        $dir = dirname($out);
        if (!is_dir($dir)) {
            if (!is_dir($dir) && !mkdir($dir, recursive: true) && !is_dir($dir)) {
                throw new CompilerException($this::class, 'Cannot create directory: ' . $dir);
            }
        }

        return ['in' => $in, 'out' => $out];
    }
}
