<?php

declare(strict_types=1);

namespace Zarthus\World\Environment;

use Psr\Log\LogLevel;

/**
 * @psalm-internal \Zarthus\World\Environment
 */
final class Production implements EnvironmentInterface
{
    public function get(EnvVar $var): mixed
    {
        return match ($var) {
            EnvVar::Name => 'Production',
            EnvVar::LogLevel => LogLevel::ALERT,
            EnvVar::Development => false,
            EnvVar::HttpListeners => [],
            EnvVar::HttpCertificatePath => null,
            EnvVar::HttpBaseDir => '/',
            EnvVar::Sass => true,
            EnvVar::Compress => false,
        };
    }
}
