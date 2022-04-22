<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;

final class CopyAssets implements CommandInterface
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
        $finder = $this->createFinder($inDir);

        $this->getLogger()->debug("Input directory: {$inDir}");
        $this->getLogger()->debug("Output directory: {$outDir}");

        $this->fs->mkdir($outDir);
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
        $inDir = Path::www(true);
        $outDir = Path::www(false);

        $command->setName('copy:assets');
        $command->setDescription('Copy non-compiled sources');
        $command->addArgument('directory-in', InputArgument::OPTIONAL, 'The template directory', $inDir);
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The output directory', $outDir);
    }

    private function createFinder(string $in): Finder
    {
        return Finder::create()
            ->in($in)
            ->exclude('articles')
            ->exclude('html')
            ->exclude('sass')
            ->exclude('scss')
            ->ignoreVCS(true)
            ->depth(0)
            ->directories();
    }

    public function supportsAsync(): bool
    {
        return true;
    }
}
