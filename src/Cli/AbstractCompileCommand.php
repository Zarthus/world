<?php
declare(strict_types=1);

namespace Zarthus\World\App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Command\CommandInterface;
use Zarthus\World\Command\CommandResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Exception\CompilerException;

abstract class AbstractCompileCommand implements CommandInterface
{
    use ResolvableNameTrait;
    use LogAwareTrait;

    abstract protected function getCompiler(): CompilerInterface;

    /**
     * @return array{in: string, out: string}
     *
     * @throws CompilerException
     */
    protected function validate(InputInterface $input): array
    {
        if (!file_exists($inDir = (string) $input->getArgument('directory-in'))) {
            throw new CompilerException($this->getCompiler()::class, 'Directory (input) does not exist.');
        }
        if (!file_exists($outDir = (string) $input->getArgument('directory-out'))) {
            throw new CompilerException($this->getCompiler()::class, 'Directory (output) does not exist.');
        }

        return ['in' => $inDir, 'out' => $outDir];
    }

    public function execute(InputInterface $input, OutputInterface $output): CommandResult
    {
        ['in' => $inDir, 'out' => $outDir] = $this->validate($input);

        $errored = false;
        $style = (new SymfonyStyle($input, $output));

        try {
            $this->getCompiler()->compile(new CompilerOptions($inDir, $outDir, false));
        } catch (CompilerException $e) {
            $errored = true;
            $style->error($e->getMessage());
        }

        return $this->postExecute($style, $outDir, $errored);
    }

    protected function postExecute(SymfonyStyle $style, string $outDir, bool $errored): CommandResult
    {
        if ($errored) {
            $style->caution("There were some compilation errors!");
            return CommandResult::Error;
        }
        $style->success("Compiled sources into $outDir");
        return CommandResult::Ok;
    }

    public function configure(Command $command): void
    {
        $command->setDescription('Compiles sources using ' . $this->getCompiler()::class);

        $command->addArgument('directory-in', InputArgument::OPTIONAL, 'The input directory');
        $command->addArgument('directory-out', InputArgument::OPTIONAL, 'The output directory');
    }

    public function supportsAsync(): bool
    {
        return true;
    }
}
