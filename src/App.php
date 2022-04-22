<?php

declare(strict_types=1);

namespace Zarthus\World\App;

use Monolog\Logger;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;

final class App
{
    public static function name(): string
    {
        return 'World Builder';
    }

    public static function version(): string
    {
        return '1.0';
    }

    public static function getContainer(): Container
    {
        return Container::create();
    }

    public static function getEnvironment(): Environment
    {
        return Container::create()->get(Environment::class);
    }

    public static function getLogger(?string $class = null): Logger
    {
        $logger = Container::create()->get(Logger::class);

        if (null !== $class) {
            return $logger->withName(str_replace('Zarthus\\World\\', '', $class));
        }

        return $logger;
    }
}
