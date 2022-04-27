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
use Symfony\Component\Finder\Finder;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;

/**
 * Not strictly just a `copy`  as it also `touches` files.
 * Generates two files to help support GitHub Pages:
 * - .nojekyll (solves being unable to list `_`-prefixed directories)
 * - CNAME (copies CNAME from root)
 * - Copies errors/*.html to root as well
 *
 * This command should run AFTER compilation has succeeded.
 */
final class PublishPages implements CommandInterface
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
            $outDir,
        ] = [
            (string) $input->getArgument('directory-out'),
        ];

        if ($input->getOption('nojekyll')) {
            $this->getLogger()->debug('Creating nojekyll file');
            $this->fs->touch($outDir . '/.nojekyll');
        }

        if ($input->getOption('cname')) {
            $this->getLogger()->debug('Mirroring CNAME file');
            $cname = Path::root() . '/CNAME';
            if ($this->fs->exists($cname)) {
                $this->fs->copy($cname, $outDir . '/CNAME');
            }
        }

        if ($input->getOption('errorpages')) {
            $errorPages = Path::www(false) . '/errors';
            $this->getLogger()->error($errorPages);
            $finder = Finder::create()
                ->in($errorPages)
                ->name('*.html')
                ->depth(0)
                ->files();
            foreach ($finder as $errorPage) {
                $this->fs->copy($errorPage->getPathname(), $outDir . '/' . $errorPage->getFilename());
            }
        }

        return CommandResult::Ok;
    }

    public function configure(Command $command): void
    {
        $outDir = Path::www(false);

        $command->setDescription('Prepare for shipping to GitHub Pages');
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The output directory', $outDir);

        $command->addOption('nojekyll', null, InputOption::VALUE_NONE, 'Adds a .nojekyll file');
        $command->addOption('cname', null, InputOption::VALUE_NONE, 'Copies CNAME from root if exists');
        $command->addOption('errorpages', null, InputOption::VALUE_NONE, 'Copies pre-compiled html error pages from `errors` to `/`');
    }

    public function supportsAsync(): bool
    {
        return false;
    }
}
