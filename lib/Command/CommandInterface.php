<?php

declare(strict_types=1);

namespace Zarthus\World\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zarthus\World\App\Cli\AsyncCommandInterface;

interface CommandInterface extends AsyncCommandInterface
{
    public function getName(): string;

    public function execute(InputInterface $input, OutputInterface $output): CommandResult;

    public function configure(Command $command): void;
}
