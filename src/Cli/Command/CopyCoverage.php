<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;

final class CopyCoverage implements CommandInterface
{
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

        $style = (new SymfonyStyle($input, $output));

        try {
            $finder = $this->createFinder($inDir);
        } catch (DirectoryNotFoundException $e) {
            $this->getLogger()->error($e->getMessage());

            return $input->getOption('allow-failure')
                ? CommandResult::Ok
                : CommandResult::Error;
        }

        $this->getLogger()->debug("Input directory: {$inDir}");
        $this->getLogger()->debug("Output directory: {$outDir}");

        $this->fs->mkdir($outDir);
        foreach (['dashboard.html', 'index.html'] as $file) {
            $this->getLogger()->debug("Copy $file");
            $this->fs->copy($inDir . '/' . $file, $outDir . '/' . $file);
        }
        foreach ($finder as $item) {
            $this->getLogger()->debug("Copy {$item->getRelativePathname()}");
            $this->fs->mirror(
                "$inDir/{$item->getRelativePathname()}",
                "$outDir/{$item->getRelativePathname()}",
                null,
                options: ['override' => true, 'copy_on_windows' => true, 'delete' => true]
            );
        }

        $style->success("Copied sources into $outDir");
        return CommandResult::Ok;
    }

    public function configure(Command $command): void
    {
        $inDir = Path::tests() . '/coverage/html';
        $outDir = Path::www(false) . '/coverage';

        $command->setDescription('Copy coverage files from testsuite');
        $command->addArgument('directory-in', InputArgument::OPTIONAL, 'The template directory', $inDir);
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The output directory', $outDir);
        $command->addOption('allow-failure', null, InputOption::VALUE_NONE, 'Allows failure of the command');
    }

    private function createFinder(string $in): Finder
    {
        return Finder::create()
            ->in($in)
            ->ignoreVCS(true)
            ->depth(0)
            ->directories();
    }

    public function supportsAsync(): bool
    {
        return true;
    }
}
