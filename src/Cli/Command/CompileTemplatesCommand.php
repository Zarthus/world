<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Zarthus\World\App\Cli\AbstractCompileCommand;
use Zarthus\World\App\Path;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\Compilers\TwigCompiler;

final class CompileTemplatesCommand extends AbstractCompileCommand
{
    public function __construct(
        private readonly TwigCompiler $compiler,
    ) {
    }

    protected function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }

    public function configure(Command $command): void
    {
        $inDir = Path::templates(true);
        $outDir = Path::templates(false);

        $command->setDescription('Compiles template sources');

        $command->addArgument('directory-in', InputArgument::OPTIONAL, 'The template input directory', $inDir);
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The html output directory', $outDir);
    }
}
