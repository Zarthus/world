<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Unit\App;

use PHPUnit\Framework\TestCase;
use Zarthus\World\App\Bootstrap;

final class BootstrapTest extends TestCase
{
    public function testSetup(): void
    {
        Bootstrap::init();
        $this->assertTrue(true);
    }
}
