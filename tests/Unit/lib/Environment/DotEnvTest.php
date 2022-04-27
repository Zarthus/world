<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Zarthus\World\Container\DotEnv\DotEnv;
use Zarthus\World\Test\Framework\LoadsFixtures;

final class DotEnvTest extends TestCase
{
    use LoadsFixtures;

    public function testDotEnv(): void
    {
        $result = DotEnv::fromPath($this->fixture('DotEnv/test.env'));

        $this->assertTrue($result);
        $this->assertIsNumeric(trim(getenv('ZARTHUS_DOTENV_TEST_NUMBER')));
        $this->assertNotEmpty(trim(getenv('ZARTHUS_DOTENV_TEST_STRING')));
    }
}
