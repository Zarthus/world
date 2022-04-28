<?php

declare(strict_types=1);

namespace Zarthus\World\App;

use Zarthus\World\App\Exception\PhpError;
use Zarthus\World\Container\Container;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;
use Zarthus\World\Environment\EnvVar;

final class Bootstrap
{
    /** @psalm-suppress InternalMethod */
    public static function init(): void
    {
        date_default_timezone_set('Etc/UTC');

        $container = Container::create();
        $finder = \Symfony\Component\Finder\Finder::create()->in(__DIR__ . '/ServiceProvider');
        foreach ($finder as $item) {
            /** @var class-string<AbstractServiceProvider> $classString */
            $classString = __NAMESPACE__ . "\\ServiceProvider\\{$item->getFilenameWithoutExtension()}";

            $container->addServiceProvider(new $classString());
        }

        $env = App::getEnvironment();
        App::getLogger()->debug(sprintf(
            'Initialized environment: %s (%s)',
            $env->getString(EnvVar::Name),
            $env->getBool(EnvVar::Development) ? 'dev' : 'live',
        ));

        error_reporting(E_ALL);
        set_error_handler(self::errorHandler(), E_ALL);
    }

    private static function errorHandler(): callable
    {
        return static fn (int $errno, string $errstr, string $errfile, int $errline) => throw new PhpError($errstr, $errno, $errfile, $errline);
    }
}
