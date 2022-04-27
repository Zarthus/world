<?php
declare(strict_types=1);

namespace Zarthus\World\Container\DotEnv;

use Zarthus\World\App\Path;
use Zarthus\World\Environment\EnvironmentInterface;
use Zarthus\World\Environment\EnvVar;

/**
 * Helper to load `.env`, `.env.Development`, etc.
 * Needs to happen before Environment is initialized, as the environment will depend on it.
 */
final class DotEnv
{
    /**
     * @return bool if the env was modified.
     */
    public static function fromPath(string $path): bool
    {
        return self::parse($path);
    }

    /**
     * @psalm-suppress PossiblyInvalidCast
     * @psalm-suppress InternalMethod
     * @return bool if the env was modified.
     */
    public static function fromEnvironment(?EnvironmentInterface $environment): bool
    {
        $root = Path::root();

        $file = sprintf('.env%s', $environment !== null ? ('.' . ((string)$environment->get(EnvVar::Name))) : '');
        $path = $root . '/' . $file;

        return self::parse($path);
    }

    private static function parse(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $loaded = false;
        $lines = explode("\n", file_get_contents($path));
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $loaded = true;
            putenv($line);
        }
        return $loaded;
    }
}
