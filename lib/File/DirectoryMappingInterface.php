<?php

declare(strict_types=1);

namespace Zarthus\World\File;

interface DirectoryMappingInterface
{
    public function resolveDirectory(string $path): string;

    public function resolveFilePath(string $path): string;
}
