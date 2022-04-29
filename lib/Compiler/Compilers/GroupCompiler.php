<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Compiler\CompileResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class GroupCompiler implements CompilerInterface
{
    use LogAwareTrait;

    /**
     * @param CompilerInterface[] $compilers The order matters as multiple compilers might {@see supports} the same item.
     */
    public function __construct(
        private readonly array $compilers,
    ) {
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        try {
            $this->getCompiler($options, $template);
        } catch (TemplateNotFoundException) {
            return false;
        }
        return true;
    }

    public function compile(CompilerOptions $options): void
    {
        $success = false;
        $lastException = null;

        foreach ($this->getCompilers($options, null) as $compiler) {
            try {
                $compiler->compile($options);
                $success = true;
            } catch (CompilerException $e) {
                $lastException = $e;
                // let the next-supported compiler try.
            }
        }

        if (!$success) {
            if (null !== $lastException) {
                throw $lastException;
            }

            throw new TemplateNotFoundException('(null)', $options, $this::class);
        }
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        $this->getCompiler($options, $template)->compileTemplate($options, $template);
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        return $this->getCompiler($options, $template)->renderTemplate($options, $template);
    }

    /**
     * @param class-string<CompilerInterface>[] $excludes
     *
     * @throws TemplateNotFoundException
     */
    private function getCompiler(CompilerOptions $options, ?string $template, array $excludes = []): CompilerInterface
    {
        $compilers = $this->getCompilers($options, $template, $excludes);

        if ([] === $compilers) {
            $this->getLogger()->debug(sprintf('No compilers support %s@%s', basename($options->getInDirectory()), $template ?? '(null)'));
            throw new TemplateNotFoundException($template ?? '(null)', $options, $this::class);
        }

        return current($compilers);
    }

    /**
     * @param class-string<CompilerInterface>[] $excludes
     *
     * @return list<CompilerInterface>
     */
    private function getCompilers(CompilerOptions $options, ?string $template, array $excludes = []): array
    {
        $supported = [];

        foreach ($this->compilers as $compiler) {
            if (in_array($compiler::class, $excludes, true)) {
                continue;
            }

            if ($compiler->supports($options, $template)) {
                $this->getLogger()->debug(sprintf('Compiler %s supports %s@%s', $compiler::class, basename($options->getInDirectory()), $template ?? '(null)'));
                $supported[] = $compiler;
            }
        }

        return $supported;
    }
}
