<?php

declare(strict_types=1);

namespace Zarthus\World\Exception;

final class TemplateIllegalException extends CompilerException
{
    public function __construct(string $template, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Template "%s" has an illegal name',
            $template,
        ), previous: $previous);
    }
}
