<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Amp\Loop;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zarthus\World\App\Cli\AsyncCommandInterface;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;
use Zarthus\World\Exception\AppException;
use function Amp\call;

final class RunMultipleCommand implements CommandInterface
{
    use LogAwareTrait;

    private ?\Symfony\Component\Console\Application $application = null;
    /** @see getLogger */
    private readonly Logger $logger;

    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger->withName('GroupCommand');
    }

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        $style = new SymfonyStyle($input, $output);

        if (null === $this->application) {
            $style->error("Application is null, please make sure to run configure() first!");
            return CommandResult::Error;
        }

        /** @var CommandResult[] $results */
        $results = [];

        if ($input->getOption('async')) {
            Loop::run(function () use ($input, $output, &$results) {
                /** @var string[] $cmds */
                $cmds = $input->getArgument('cmd');

                $commands = yield call(fn () => $this->validateCommands($cmds));
                /** @var CommandResult[] $results */
                $results = yield from $this->runCommands($commands, $output, true);
            });
        } else {
            /** @var string[] $cmds */
            $cmds = $input->getArgument('cmd');

            $commands = $this->validateCommands($cmds);
            $results = $this->runCommands($commands, $output, false);
        }

        $finalResult = CommandResult::Ok;
        /** @var CommandResult[] $results */
        foreach ($results as $result) {
            if (CommandResult::Ok === $result) {
                continue;
            }

            $finalResult = $result;
        }
        return $finalResult;
    }

    public function configure(Command $command): void
    {
        $command->setDescription('Run multiple commands');
        $command->addOption('async', null, InputOption::VALUE_NONE, 'Runs commands async');
        $command->addArgument('cmd', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Commands to run');
        $command->setProcessTitle('Running Multiple Commands..');
        $this->application ??= $command->getApplication();
    }

    public function supportsAsync(): bool
    {
        return false;
    }

    private function getLogger(string $command): Logger
    {
        return $this->logger->withName($command);
    }

    /**
     * @param string[] $cmds
     * @return Command[]
     */
    private function validateCommands(array $cmds): array
    {
        \assert(!is_null($this->application));

        /** @var Command[] $commands */
        $commands = [];
        // Validate
        foreach ($cmds as $command) {
            /**
             * @var Command&AsyncCommandInterface $cmd
             */
            $cmd = $this->application->find($command);

            if (!$cmd->supportsAsync()) {
                throw new AppException('Command ' . ($cmd->getName() ?? $cmd::class) . ' does not support async calls.');
            }

            $commands[] = $cmd;
        }
        return $commands;
    }

    /**
     * @param Command[] $commands
     *
     * @return \Generator|CommandResult[]
     */
    private function runCommands(array $commands, OutputInterface $output, bool $async): array|\Generator
    {
        $results = [];

        foreach ($commands as $command) {
            $name = $command->getName() ?? 'NULL';

            $this->getLogger($name)->debug("Running: $name");
            $args = [new ArgvInput([], $command->getDefinition()), $output];
            if ($async) {
                $result = yield call([$command, 'run'], ...$args);
            } else {
                $result = $command->run(...$args);
            }
            $this->getLogger($name)->debug("Command $name exited with $result");

            if (0 !== $result) {
                $this->getLogger($name)->error("Command $name exited with non-zero ($result)");
                $output->writeln("Command $name exited with non-zero ($result)");
            }
            $results[] = match ($result) {
                0 => CommandResult::Ok,
                default => CommandResult::Error,
            };
        }

        return $results;
    }

    public function getName(): string
    {
        return 'multi';
    }
}
