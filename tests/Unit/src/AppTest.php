<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Unit\App;

use PHPUnit\Framework\TestCase;
use Zarthus\World\App\App;

final class AppTest extends TestCase
{
    public function testName(): void
    {
        $this->assertIsString(App::name());
    }

    public function testVersion(): void
    {
        $this->assertIsNumeric(App::version());
    }

    public function testDepencyInjectionHelpers(): void
    {
        App::getContainer();
        App::getLogger(self::class);
        App::getEnvironment();
        $this->assertTrue(true);
    }
}
