<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Zarthus\World\App\Cli\AbstractCompileCommand;
use Zarthus\World\App\Path;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\Compilers\MarkdownCompiler;

final class CompileArticlesCommand extends AbstractCompileCommand
{
    public function __construct(
        private readonly MarkdownCompiler $compiler,
    ) {
    }

    protected function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }

    public function configure(Command $command): void
    {
        $inDir = Path::articles(true);
        $outDir = Path::articles(false);

        $command->setDescription('Compiles markdown sources');

        $command->addArgument('directory-in', InputArgument::OPTIONAL, 'The markdown input directory', $inDir);
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The html output directory', $outDir);
    }
}
