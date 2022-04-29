<?php

declare(strict_types=1);

namespace Zarthus\World\File;

interface MimeTypeResolverInterface
{
    public function resolve(string $path): string;
}
