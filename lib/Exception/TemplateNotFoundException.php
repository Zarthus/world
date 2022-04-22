<?php

declare(strict_types=1);

namespace Zarthus\World\Exception;

use Zarthus\World\Compiler\CompilerOptions;

final class TemplateNotFoundException extends CompilerException
{
    public function __construct(string $template, CompilerOptions $options, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Template "%s" in "%s" not found',
            $template,
            $options->getInDirectory(),
        ), previous: $previous);
    }
}
