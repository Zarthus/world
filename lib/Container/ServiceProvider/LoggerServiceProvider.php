<?php

declare(strict_types=1);

namespace Zarthus\World\Container\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Zarthus\World\App\App;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;

final class LoggerServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return Logger::class === $id;
    }

    public function register(): void
    {
        $environment = $this->getContainer()->get(Environment::class);
        $this->getContainer()
            ->add(Logger::class, new ObjectArgument($this->createLogger($environment)));
    }

    private function createLogger(Environment $environment): Logger
    {
        $logger = new Logger(
            App::name(),
            [
                new StreamHandler(STDOUT, $environment->get(EnvVar::LogLevel), true, null, false),
            ],
            [
            ],
            new \DateTimeZone('Etc/UTC'),
        );
        $logger->useMicrosecondTimestamps(false);

        return $logger;
    }
}
