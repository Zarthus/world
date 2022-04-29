<?php

declare(strict_types=1);

namespace Zarthus\World\App\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Zarthus\Sass\Sass;
use Zarthus\Sass\SassBuilder;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;

final class SassServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return Sass::class === $id;
    }

    public function register(): void
    {
        $env = $this->container->get(Environment::class);

        if ($env->getBool(EnvVar::Sass)) {
            $this->container->add(Sass::class, new ObjectArgument(SassBuilder::autodetect()));
        } else {
            $this->container->add(Sass::class, new ObjectArgument(SassBuilder::withNullHandlers()));
        }
    }
}
