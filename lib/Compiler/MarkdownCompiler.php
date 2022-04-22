<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

use League\CommonMark\CommonMarkConverter;
use Symfony\Component\Finder\Finder;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class MarkdownCompiler implements CompilerInterface
{
    use LogAwareTrait;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment,
    ) {
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        return str_contains($options->getOutDirectory(), 'articles/') ||
            str_contains($options->getInDirectory(), 'articles/') ||
            (
                null !== $template &&
                !str_contains($template, '..') &&
                str_ends_with($template, '.md')
            );
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
        $this->getLogger()->info('Compiling: ' . $template);

        [$in, $out] = $this->validate($options, $template);
        $engine = $this->createEngine();

        $input = file_get_contents($in);
        $output = $engine->convert($input);
        file_put_contents($out, $output->getContent());
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        [$in, $_out] = $this->validate($options, $template);
        $engine = $this->createEngine();

        $input = file_get_contents($in);
        return new CompileResult(CompileType::Html, $engine->convert($input)->getContent());
    }


    private function createEngine(): CommonMarkConverter
    {
        return new CommonMarkConverter();
    }

    /**
     * @return array{in: string, out: string}
     */
    private function validate(CompilerOptions $options, string $template): array
    {
        [$in, $out] = [
            $options->getInDirectory() . "/$template",
            $options->getOutDirectory() . "/" . str_replace('.md', '.html', $template),
        ];

        if (!file_exists($in)) {
            throw new TemplateNotFoundException($template, $options);
        }

        $dir = dirname($out);
        if (!is_dir($dir)) {
            if (!is_dir($dir) && !mkdir($dir, recursive: true) && !is_dir($dir)) {
                throw new CompilerException('Cannot create directory: ' . $dir);
            }
        }

        return ['in' => $in, 'out' => $out];
    }
}
