<?php

declare(strict_types=1);

namespace Zarthus\World\Environment;

use Psr\Log\LogLevel;

/**
 * @psalm-internal \Zarthus\World\Environment
 */
final class Tests implements EnvironmentInterface
{
    public function get(EnvVar $var): mixed
    {
        return match ($var) {
            EnvVar::Name => 'Tests',
            EnvVar::Development => true,
            EnvVar::LogToStdout => false,
            EnvVar::LogLevel => LogLevel::DEBUG,
            EnvVar::HttpListeners => [],
            EnvVar::HttpCertificatePath => null,
            EnvVar::HttpBaseDir => '/',
            EnvVar::Sass => true,
            EnvVar::Compress => false,
        };
    }
}
