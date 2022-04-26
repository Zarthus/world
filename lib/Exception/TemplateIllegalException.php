<?php

declare(strict_types=1);

namespace Zarthus\World\Exception;

use Zarthus\World\Compiler\CompilerInterface;

final class TemplateIllegalException extends CompilerException
{
    /**
     * @param string $template
     * @param null|class-string<CompilerInterface> $compiler
     * @param \Throwable|null $previous
     */
    public function __construct(
        private readonly string $template,
        ?string $compiler = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $compiler,
            sprintf(
                'Template "%s" has an illegal name',
                $this->template
            ),
            previous: $previous
        );
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
