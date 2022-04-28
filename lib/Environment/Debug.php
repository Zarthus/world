<?php

declare(strict_types=1);

namespace Zarthus\World\Environment;

use Psr\Log\LogLevel;

/**
 * Like {@see Development},
 * - with verbose logging
 * - xdebug attachment is easier due to open HTTP ports
 *
 * @psalm-internal \Zarthus\World\Environment
 */
final class Debug implements EnvironmentInterface
{
    public function get(EnvVar $var): mixed
    {
        return match ($var) {
            EnvVar::Name => 'Debug',
            EnvVar::Development => true,
            EnvVar::LogLevel => LogLevel::DEBUG,
            EnvVar::HttpListeners => ['https://127.0.0.1:4443', 'https://[::1]:4443', 'http://127.0.0.1:8080', 'http://[::1]:8080'],
            EnvVar::HttpCertificatePath => '{root}/ca/server.pem',
            EnvVar::HttpBaseDir => '/',
            EnvVar::Sass => true,
            EnvVar::Compress => false,
        };
    }
}
