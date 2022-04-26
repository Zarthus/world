<?php

declare(strict_types=1);

namespace Zarthus\World\Exception;

use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;

final class TemplateNotFoundException extends CompilerException
{
    /**
     * @param string $template
     * @param CompilerOptions $options
     * @param null|class-string<CompilerInterface> $compiler
     * @param \Throwable|null $previous
     */
    public function __construct(
        private readonly string $template,
        private readonly CompilerOptions $options,
        ?string $compiler = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $compiler,
            sprintf(
                'Template "%s" in "%s" not found',
                $this->template,
                $this->options->getInDirectory(),
            ),
            previous: $previous
        );
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
