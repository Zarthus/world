<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Zarthus\World\App\Cli\AbstractCompileCommand;
use Zarthus\World\App\Path;
use Zarthus\World\Command\CommandResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\Compilers\GroupCompiler;
use Zarthus\World\Exception\CompilerException;

final class CompileCommand extends AbstractCompileCommand
{
    public function __construct(
        private readonly GroupCompiler $compiler,
    ) {
    }

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        ['in' => $inDir, 'out' => $outDir] = $this->validate($input);
        $continue = (bool)$input->getOption('continue-on-error');

        $finder = Finder::create()
            ->in($inDir)
            ->depth(0)
            ->exclude('assets')
            ->exclude('javascript')
            ->directories();
        $style = new SymfonyStyle($input, $output);
        $errored = false;

        foreach ($finder as $directory) {
            if (!$directory->isDir()) {
                continue;
            }

            $options = new CompilerOptions(
                $inDir . '/' . $directory->getRelativePathname(),
                $outDir . '/' . $directory->getRelativePathname(),
                false,
            );
            try {
                $this->compiler->compile($options);
            } catch (CompilerException $e) {
                $errored = true;

                $style->error($e->getMessage());

                if (!$continue) {
                    return CommandResult::Error;
                }
            }
        }

        return $this->postExecute($style, $outDir, $errored);
    }

    public function configure(Command $command): void
    {
        $inDir = Path::www(true);
        $outDir = Path::www(false);

        $command->setDescription('Iterate over directories in directory-in with depth of 1 and compile all sources.');
        $command->addArgument('directory-in', InputArgument::OPTIONAL, 'The root www folder (input)', $inDir);
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The root www folder (output)', $outDir);
        $command->addOption('continue-on-error', null, InputOption::VALUE_NONE, 'Continue compilation on error');
    }

    protected function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }
}
