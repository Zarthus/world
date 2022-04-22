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
        if (($this instanceof Command || $this instanceof CommandInterface) && null !== $this->getName()) {
            return App::getLogger("cmd::" . $this->getName());
        }
        return App::getLogger(static::class);
    }
}
