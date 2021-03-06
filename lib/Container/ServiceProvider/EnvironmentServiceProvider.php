<?php

declare(strict_types=1);

namespace Zarthus\World\Container\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Zarthus\World\Container\DotEnv\DotEnv;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvironmentInterface;

final class EnvironmentServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return EnvironmentInterface::class === $id || Environment::class === $id;
    }

    public function register(): void
    {
        if (empty(getenv('LIEFLAND_ENVIRONMENT'))) {
            DotEnv::fromEnvironment(null);
        }

        $environment = $this->createEnvironment();
        if (DotEnv::fromEnvironment($environment)) {
            // if env was modified, reload it.
            $environment = $this->createEnvironment();
        }

        $this->getContainer()->add(EnvironmentInterface::class, $environment);
        $this->getContainer()->add(Environment::class, new ObjectArgument(new Environment($environment)));
    }

    private function createEnvironment(): EnvironmentInterface
    {
        $env = getenv('LIEFLAND_ENVIRONMENT');
        if (empty($env)) {
            $env = 'Production';
        }

        /** @var class-string<EnvironmentInterface> $environment */
        $environment = '\\Zarthus\\World\\Environment\\' . $env;
        return new $environment();
    }
}
