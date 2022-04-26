<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Zarthus\World\App\Cli\AbstractCompileCommand;
use Zarthus\World\App\Path;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\Compilers\SassCompiler;

final class CompileSassCommand extends AbstractCompileCommand
{
    public function __construct(
        private readonly SassCompiler $compiler,
    ) {
    }

    protected function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }

    public function configure(Command $command): void
    {
        $inDir = Path::css(true);
        $outDir = Path::css(false);

        $command->setAliases(['compile:scss']);
        $command->setDescription('Compiles SASS and SCSS sources');

        $command->addArgument('directory-in', InputArgument::OPTIONAL, 'The sass input directory or file', $inDir);
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The css output directory or file', $outDir);
    }
}
