<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

use Zarthus\World\Exception\TemplateNotFoundException;

final class GroupCompiler implements CompilerInterface
{
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
        $this->getCompiler($options, null)->compile($options);
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        $this->getCompiler($options, null)->compileTemplate($options, $template);
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        return $this->getCompiler($options, null)->renderTemplate($options, $template);
    }

    /**
     * @throws TemplateNotFoundException
     */
    private function getCompiler(CompilerOptions $options, ?string $template): CompilerInterface
    {
        foreach ($this->compilers as $compiler) {
            if ($compiler->supports($options, $template)) {
                return $compiler;
            }
        }

        throw new TemplateNotFoundException($template ?? 'main', $options);
    }
}
