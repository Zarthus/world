<?php

declare(strict_types=1);

namespace Zarthus\World\App;

use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Zarthus\World\Command\CommandInterface;

trait LogAwareTrait
{
    private function getLogger(): Logger
    {
        return App::getLogger($this->getLoggerName());
    }

    private function getLoggerName(): string
    {
        if (($this instanceof Command || $this instanceof CommandInterface)) {
            return "cmd::" . $this->getName();
        }
        return static::class;
    }
}
