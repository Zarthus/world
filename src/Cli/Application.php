<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli;

use Amp\Loop;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Zarthus\World\App\App;
use Zarthus\World\Command\CommandFactory;
use Zarthus\World\Command\CommandInterface;

final class Application
{
    /** @param CommandInterface[] $commands */
    public function __construct(
        private readonly SymfonyApplication $console,
        array $commands = [],
    ) {
        $this->addCommands($commands);
    }

    public function help(): string
    {
        return $this->console->getHelp();
    }

    public function exec(?array $argv = null): void
    {
        $this->console->run(new ArgvInput($argv));
    }

    /** @param CommandInterface[] $commands */
    private function addCommands(array $commands): void
    {
        $container = App::getContainer();
        $commandFactory = $container->get(CommandFactory::class);

        if ([] === $commands) {
            $finder = \Symfony\Component\Finder\Finder::create()->in(__DIR__ . '/Command');

            foreach ($finder as $item) {
                /** @var class-string<CommandInterface> $classString */
                $classString = __NAMESPACE__ . "\\Command\\{$item->getFilenameWithoutExtension()}";

                $commands[] = $container->get($classString);
            }
        }

        foreach ($commands as $command) {
            $this->console->add($commandFactory->asCommand($this->console, $command));
        }
    }
}
