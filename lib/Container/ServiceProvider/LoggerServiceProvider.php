<?php

declare(strict_types=1);

namespace Zarthus\World\Container\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Monolog\Handler\RotatingFileHandler;
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
        /** @var Environment $environment */
        $environment = $this->getContainer()->get(Environment::class);
        $this->getContainer()
            ->add(Logger::class, new ObjectArgument($this->createLogger($environment)));
    }

    private function createLogger(Environment $environment): Logger
    {
        $handlers = [];

        if (PHP_SAPI === 'cli' && $environment->getBool(EnvVar::LogToStdout)) {
            $handlers[] = new StreamHandler(STDOUT, $environment->get(EnvVar::LogLevel), true, null, false);
        }
        if (!empty(getenv('LIEFLAND_LOG_PATH'))) {
            $logFile = getenv('LIEFLAND_LOG_PATH') . $environment->getString(EnvVar::Name) . '.log';
            $logFile = str_replace('{root}', $this->container->get('paths.root'), $logFile);
            $handlers[] = new RotatingFileHandler($logFile, 2, $environment->get(EnvVar::LogLevel));
        }

        $logger = new Logger(
            App::name(),
            $handlers,
            [],
            new \DateTimeZone('Etc/UTC'),
        );
        $logger->useMicrosecondTimestamps(false);

        return $logger;
    }
}
