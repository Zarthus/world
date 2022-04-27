<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;

final class CleanProjectCommand implements CommandInterface
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
        private readonly array $ensureDirectoriesExist = [],
    ) {
    }

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        /**
         * @var string[] $files
         * @var string[] $directories
         */
        $files = [...$this->files];
        $directories = [...$this->directories];

        if ($input->getOption('fresh')) {
            $files[] = Path::root() . '/' . '.env';
            $files[] = Path::root() . '/' . '.php-cs-fixer';
            $directories[] = Path::root() . '/' . 'ca';
        }

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

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

        try {
            $this->fs->remove($flatFiles);
        } catch (IOException $e) {
            $this->getLogger()->error("Failed to clean: " . $e->getMessage());
            if (!$input->getOption('allow-failure')) {
                throw $e;
            }
        } finally {
            if (!empty($this->ensureDirectoriesExist)) {
                try {
                    $this->fs->mkdir($this->ensureDirectoriesExist);
                } catch (IOException $e) {
                    $this->getLogger()->error("Failed to ensure directories exist: " . $e->getMessage());
                }
            }
        }

        return CommandResult::Ok;
    }

    public function configure(Command $command): void
    {
        $command->setDescription('Cleans up temporary files');
        // I suspect that due to some PATH conflict sometimes `clean` is a conflict.
        // > cli clean --allow-failure
        // Cannot open assembly 'clean': No such file or directory.
        $command->setAliases(['clean']);
        $command->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not perform any deletions');
        $command->addOption('allow-failure', null, InputOption::VALUE_NONE, 'Return exit code 0 regardless if not everything successfully deleted');
        $command->addOption('fresh', null, InputOption::VALUE_NONE, 'Also cleans important config files and credentials');
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
