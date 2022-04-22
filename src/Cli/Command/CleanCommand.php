<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;

final class CleanCommand implements CommandInterface
{
    use LogAwareTrait;
    use ResolvableNameTrait;

    /**
     * @param string[] $directories
     * @param string[] $files
     */
    public function __construct(
        private readonly Filesystem $fs,
        private readonly array $directories,
        private readonly array $files,
    ) {
    }

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        /**
         * @var string[] $files
         */
        $files = [...$this->files];

        foreach ($this->directories as $directory) {
            foreach ($this->collectDirectoryFiles($directory) as $file) {
                $files[] = $file;
            }
        }

        $flatFiles = array_filter(array_unique($files));
        $this->getLogger()->debug("Cleaning files: " . count($flatFiles));
        if (empty($flatFiles)) {
            $this->getLogger()->info("Nothing to clean");
            return CommandResult::Ok;
        }

        if ($input->getOption('dry-run')) {
            $this->getLogger()->info('Dry run, not performing any changes..');
            $this->getLogger()->info('Would clean ' . count($flatFiles) . ' files');
            return CommandResult::Ok;
        }

        $this->fs->remove($flatFiles);

        return CommandResult::Ok;
    }

    public function configure(Command $command): void
    {
        $command->setDescription('Cleans up temporary files');
        $command->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not perform any deletions');
    }

    public function supportsAsync(): bool
    {
        return false;
    }

    /**
     * @param string $directory
     * @return string[]
     */
    private function collectDirectoryFiles(string $directory): array
    {
        $finder = Finder::create()->in($directory);
        $files = [];

        foreach ($finder as $item) {
            if ($item->isDir()) {
                $files = array_merge($files, $this->collectDirectoryFiles($item->getPathname()));
            }

            $files[] = $item->getPathname();
        }

        return $files;
    }
}
