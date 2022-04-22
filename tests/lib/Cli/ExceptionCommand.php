<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Lib\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;
use Zarthus\World\Exception\FatalAppException;

class ExceptionCommand implements CommandInterface
{
    use ResolvableNameTrait;

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        throw new FatalAppException('Mock error!');
    }

    public function configure(Command $command): void
    {
        $command->setDescription('Test command');
    }

    public function supportsAsync(): bool
    {
        return false;
    }
}
