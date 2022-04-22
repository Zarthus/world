<?php

declare(strict_types=1);

namespace Zarthus\World\Environment;

/**
 * @psalm-internal \Zarthus\World\Environment
 */
interface EnvironmentInterface
{
    /**
     * @param EnvVar $var
     *
     * @return ?scalar|array
     */
    public function get(EnvVar $var);
}
