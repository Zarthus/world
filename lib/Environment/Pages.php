<?php

declare(strict_types=1);

namespace Zarthus\World\Environment;

use Psr\Log\LogLevel;

/**
 * EnvironmentInterface for github-pages
 *
 * @psalm-internal \Zarthus\World\Environment
 */
final class Pages implements EnvironmentInterface
{
    public function get(EnvVar $var): mixed
    {
        return match ($var) {
            EnvVar::Name => 'Pages',
            EnvVar::Development => false,
            EnvVar::LogLevel => LogLevel::ALERT,
            EnvVar::LogToStdout => true,
            EnvVar::HttpListeners => [],
            EnvVar::HttpCertificatePath => null,
            EnvVar::HttpBaseDir => '/',
            EnvVar::Sass => true,
            EnvVar::Compress => false,
        };
    }
}
