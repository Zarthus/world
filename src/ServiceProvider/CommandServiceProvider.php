<?php

declare(strict_types=1);

namespace Zarthus\World\App\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Symfony\Component\Filesystem\Filesystem;
use Zarthus\World\App\Cli\Command\CleanProjectCommand;
use Zarthus\World\App\Path;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;

final class CommandServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return CleanProjectCommand::class === $id;
    }

    public function register(): void
    {
        $www = Path::www(false);
        $directories = [
            Path::tests() . '/coverage',
            $www,
            Path::tmp(),
        ];
        $directoriesToExist = [
            $www,
            $www . '/api',
            $www . '/articles',
            $www . '/assets',
            $www . '/css',
            $www . '/html',
            $www . '/javascript',
        ];
        $files = [];
        $command = new CleanProjectCommand(
            $this->container->get(Filesystem::class),
            $directories,
            $files,
            $directoriesToExist,
        );

        $this->container->add(CleanProjectCommand::class, new ObjectArgument($command));
    }
}
