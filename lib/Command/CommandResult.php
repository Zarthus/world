<?php

declare(strict_types=1);

namespace Zarthus\World\Command;

enum CommandResult
{
    /**
     * Halt command evaluation
     */
    case Ok;
    /**
     * CommandInterface errored
     */
    case Error;
}
