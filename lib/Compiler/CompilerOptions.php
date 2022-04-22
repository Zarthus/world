<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

final class CompilerOptions
{
    public function __construct(
        private readonly string $inDirectory,
        private readonly string $outDirectory,
        private readonly bool $isLiveCompilation = true,
    ) {
    }

    public function getInDirectory(): string
    {
        return $this->inDirectory;
    }

    public function getOutDirectory(): string
    {
        return $this->outDirectory;
    }

    public function isLiveCompilation(): bool
    {
        return $this->isLiveCompilation;
    }
}
