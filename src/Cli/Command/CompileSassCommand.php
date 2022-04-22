<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zarthus\World\App\Cli\ResolvableNameTrait;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\SassCompiler;
use Zarthus\World\Exception\CompilerException;

final class CompileSassCommand implements CommandInterface
{
    use ResolvableNameTrait;
    use LogAwareTrait;

    public function __construct(
        private readonly SassCompiler $compiler,
    ) {
    }

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        $errored = false;

        [
            $inDir,
            $outDir,
        ] = [
            (string) $input->getArgument('directory-in'),
            (string) $input->getArgument('directory-out'),
        ];
        $style = (new SymfonyStyle($input, $output));

        try {
            $this->compiler->compile(new CompilerOptions($inDir, $outDir, false));
        } catch (CompilerException $e) {
            $errored = true;
            $style->error($e->getMessage());
        }

        if ($errored) {
            $style->caution("There were some compilation errors!");
            return CommandResult::Error;
        }
        $style->success("Compiled sources into $outDir");
        return CommandResult::Ok;
    }

    public function configure(Command $command): void
    {
        $inDir = Path::css(true);
        $outDir = Path::css(false);

        $command->setAliases(['compile:scss']);
        $command->setDescription('Compiles template sources into rendered html');
        $command->addArgument('directory-in', InputArgument::OPTIONAL, 'The sass input directory or file', $inDir);
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The css output directory or file', $outDir);
    }

    public function supportsAsync(): bool
    {
        return true;
    }
}
