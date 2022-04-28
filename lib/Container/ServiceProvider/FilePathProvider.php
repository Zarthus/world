<?php

declare(strict_types=1);

namespace Zarthus\World\Container\ServiceProvider;

use League\Container\Argument\Literal\StringArgument;

final class FilePathProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return 'paths.root' === $id;
    }

    public function register(): void
    {
        $root = dirname(__DIR__, 3);
        $this->container->add('paths.root', new StringArgument($root));
        chdir($root);
    }
}
