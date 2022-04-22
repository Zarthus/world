<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Zarthus\World\Exception\SingletonException;

final class SingletonExceptionTest extends TestCase
{
    public function testThrow(): void
    {
        $this->expectException(SingletonException::class);

        throw new SingletonException();
    }
}
