<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\App\Http\Kernel;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;

final class WebserverCommand implements CommandInterface
{
    use ResolvableNameTrait;
    use LogAwareTrait;

    public function __construct(
        private readonly Kernel $httpKernel,
    ) {
    }

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        $this->httpKernel->start();

        return CommandResult::Ok;
    }

    public function configure(Command $command): void
    {
        $command->setDescription('Starts a webserver');
        $command->setProcessTitle('HTTP Server');
    }

    public function supportsAsync(): bool
    {
        return false;
    }
}
