<?php

namespace Zarthus\World\Environment;

/**
 * @psalm-suppress PossiblyInvalidCast
 */
final class Environment implements \Stringable
{
    public function __construct(
        private readonly EnvironmentInterface $env,
    ) {
    }

    /** @return scalar|null|array */
    public function get(EnvVar $name)
    {
        return $this->env->get($name);
    }

    /**
     * @return string[]
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getStringArray(EnvVar $name): array
    {
        return (array) $this->env->get($name);
    }

    public function getString(EnvVar $name): string
    {
        return (string) $this->env->get($name);
    }

    public function getNullableString(EnvVar $name): ?string
    {
        $val = $this->env->get($name);
        if (null === $val) {
            return null;
        }
        return (string) $val;
    }

    public function getInt(EnvVar $name): int
    {
        return (int) $this->env->get($name);
    }

    public function getBool(EnvVar $name): bool
    {
        return (bool) $this->env->get($name);
    }

    public function __toString(): string
    {
        return sprintf('%s%s', $this->getString(EnvVar::Name), $this->getString(EnvVar::Development) ? ' (Dev)' : '');
    }
}
