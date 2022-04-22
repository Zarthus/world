<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Unit\App;

use PHPUnit\Framework\TestCase;
use Zarthus\World\App\App;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Environment\Tests;

final class DiTest extends TestCase
{
    /**
     * @psalm-suppress InternalClass
     * @psalm-suppress InternalMethod
     */
    public function testContainer(): void
    {
        Container::create();
        App::getLogger();
        $this->assertTrue(true);
    }

    /**
     * @psalm-suppress InternalClass
     * @psalm-suppress InternalMethod
     */
    public function testEnv(): void
    {
        $env = new Tests();
        $testEnvName = $env->get(EnvVar::Name);
        $this->assertSame($testEnvName, App::getEnvironment()->getString(EnvVar::Name));
    }
}
