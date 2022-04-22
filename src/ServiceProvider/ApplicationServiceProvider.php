<?php

declare(strict_types=1);

namespace Zarthus\World\App\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Symfony\Component\Console\Application as SymfonyApplication;
use Zarthus\World\App\App;
use Zarthus\World\App\Cli\Application;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;
use Zarthus\World\Environment\Environment;

final class ApplicationServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return Application::class === $id;
    }

    public function register(): void
    {
        $app = new SymfonyApplication(App::name() . ' (' . $this->container->get(Environment::class) . ')', App::version());

        $this->container->add(Application::class, new ObjectArgument(new Application($app)));
    }
}
