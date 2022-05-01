<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
    use ResolvableNameTrait;
    use LogAwareTrait;
    private const FILES = [
        'bootstrap-icons/font/fonts/bootstrap-icons.woff' => 'assets/fonts/bootstrap-icons.woff',
        'bootstrap-icons/font/fonts/bootstrap-icons.woff2' => 'assets/fonts/bootstrap-icons.woff2',
    ];
    private const DIRS = [
        //'@fortawesome/fontawesome-free/webfonts' => 'assets/fonts/fontawesome',
    ];

    public function __construct(
        private readonly Filesystem $fs,
    ) {
    }

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        if (!is_dir($input->getArgument('directory-in'))) {
            // Assert `npm install` has been ran.
            throw new FileNotFoundException($input->getArgument('directory-in'));
        }

        $this->handle($input, self::FILES, function (string $inPath, string $outPath) {
            $this->fs->mkdir(dirname($outPath));
            $this->fs->copy($inPath, $outPath);
        });
        $this->handle($input, self::DIRS, function (string $inPath, string $outPath) {
            $this->fs->mkdir($outPath);
            $this->fs->mirror($inPath, $outPath);
        });

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

    /**
     * @param array<string, string> $paths
     * @param \Closure(string $inPath, string $outPath):void $copyCommand
     */
    private function handle(InputInterface $input, array $paths, \Closure $copyCommand): void
    {
        [
            $inDir,
            $outDir,
        ] = [
            (string) $input->getArgument('directory-in'),
            (string) $input->getArgument('directory-out'),
        ];

        foreach ($paths as $modulePath => $outputPath) {
            $inPath = $inDir . $modulePath;
            if (!file_exists($inPath)) {
                $this->getLogger()->debug("File does not exist: $inPath");
                continue;
            }

            $outPaths = [$outDir . '/' . $outputPath];
            foreach ($outPaths as $outPath) {
                $copyCommand($inPath, $outPath);
            }
        }
    }
}
