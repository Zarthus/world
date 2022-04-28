<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;
use Zarthus\World\Exception\FileNotFoundException;

/**
 * Copies essential files from node_modules/
 * Assumes they are initialized.
 *
 * This command is only needed infrequently; whenever relevant node_modules packares are updated and once on install.
 */
final class CopyNodeModules implements CommandInterface
{
    private const FILES = [
        'bootstrap-icons/font/fonts/bootstrap-icons.woff' => 'assets/fonts/bootstrap-icons.woff',
        'bootstrap-icons/font/fonts/bootstrap-icons.woff2' => 'assets/fonts/bootstrap-icons.woff2',
    ];

    use ResolvableNameTrait;
    use LogAwareTrait;

    public function __construct(
        private readonly Filesystem $fs,
    ) {
    }

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        [
            $inDir,
            $outDir,
        ] = [
            (string) $input->getArgument('directory-in'),
            (string) $input->getArgument('directory-out'),
        ];

        if (!is_dir($inDir)) {
            // Assert `npm install` has been ran.
            throw new FileNotFoundException($inDir);
        }

        foreach (self::FILES as $modulePath => $file) {
            $path = $inDir . $modulePath;
            if (!file_exists($path)) {
                $this->getLogger()->error("File does not exist: $path");
                continue;
            }

            $outPaths = [$outDir . '/' . $file];
            if (!$input->getOption('private')) {
                $outPaths[] = str_replace('public', 'private', $outDir) . '/' . $file;
            }
            foreach ($outPaths as $outPath) {
                $this->fs->mkdir(dirname($outPath));
                $this->fs->copy($path, $outPath);
            }
        }

        return CommandResult::Ok;
    }

    public function configure(Command $command): void
    {
        $inDir = Path::root() . '/node_modules/';
        $outDir = Path::www(false);

        $command->setName('copy:node-modules');
        $command->setAliases(['copy:node']);
        $command->setDescription('Copy non-compiled sources');
        $command->addArgument('directory-in', InputArgument::OPTIONAL, 'The node_modules directory', $inDir);
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The output directory', $outDir);
    }

    public function supportsAsync(): bool
    {
        return true;
    }
}
