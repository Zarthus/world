<?php

declare(strict_types=1);

namespace Zarthus\World\Environment;

use Psr\Log\LogLevel;

/**
 * @psalm-internal \Zarthus\World\Environment
 */
final class Development implements EnvironmentInterface
{
    public function get(EnvVar $var): mixed
    {
        return match ($var) {
            EnvVar::Name => 'Development',
            EnvVar::Development => true,
            EnvVar::LogLevel => LogLevel::DEBUG,
            EnvVar::HttpListeners => ['https://127.0.0.1:4443', 'https://[::1]:4443'],
            EnvVar::HttpCertificatePath => '{root}/ca/server.pem',
            EnvVar::HttpBaseDir => '/',
            EnvVar::Sass => true,
            EnvVar::Compress => false,
        };
    }
}
