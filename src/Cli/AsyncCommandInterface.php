<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli;

interface AsyncCommandInterface
{
    public function supportsAsync(): bool;
}
