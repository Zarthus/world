<?php

declare(strict_types=1);

namespace Zarthus\World\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zarthus\World\App\Cli\AsyncCommandInterface;
use Zarthus\World\App\LogAwareTrait;

final class CommandFactory
{
    public function asCommand(Application $application, CommandInterface $command): Command
    {
        return new class ($application, $command) extends Command implements AsyncCommandInterface {
            use LogAwareTrait;

            public function __construct(
                private readonly Application $application,
                private readonly CommandInterface $command,
            ) {
                parent::__construct($this->command->getName());
                $this->setApplication($this->application);
            }

            public function getApplication(): Application
            {
                return $this->application;
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return match ($this->command->execute($input, $output)) {
                    CommandResult::Ok => 0,
                    CommandResult::Error => 1,
                    default => 254,
                };
            }

            protected function configure(): void
            {
                parent::configure();
                $this->command->configure($this);
            }

            public function supportsAsync(): bool
            {
                return $this->command->supportsAsync();
            }
        };
    }
}
