<?php

declare(strict_types=1);

namespace Zarthus\World\App\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Symfony\Component\Filesystem\Filesystem;
use Zarthus\World\App\Cli\Command\CleanCommand;
use Zarthus\World\App\Path;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;

final class CommandServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return CleanCommand::class === $id;
    }

    public function register(): void
    {
        $directories = [
            Path::tests() . '/coverage',
            Path::tmp(),
            Path::www(false),
        ];
        $files = [];
        $command = new CleanCommand($this->container->get(Filesystem::class), $directories, $files);

        $this->container->add(CleanCommand::class, new ObjectArgument($command));
    }
}
