<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Unit\App;

use PHPUnit\Framework\TestCase;
use Zarthus\World\App\Path;

final class PathTest extends TestCase
{
    /** @var class-string<Path> */
    private string $serviceClass = Path::class;

    /**
     * @return string[][]
     *
     * @psalm-return list<list<string>>
     */
    public function dataResults(): array
    {
        return [
            ['root', '@/@'],
            ['lib', '@lib@'],
            ['app', '@src@'],
            ['tests', '@tests@'],
            ['assets', '@assets@'],
            ['tmp', '@tmp@'],
        ];
    }

    /** @dataProvider dataResults */
    public function testResult(string $method, string $regexp): void
    {
        $this->assertTrue(method_exists($this->serviceClass, $method), "Expected method to exist $method");

        // If the $method doesn't take an argument it will be silently supressed,
        // if it does, it will point towards the 'dev' directory (which MUST exist),
        // the 'out' (production) directory only exists after building.
        $result = $this->serviceClass::$method(true);
        $this->assertIsString($result);
        $this->assertDirectoryExists($result);
        $this->assertDirectoryIsReadable($result);
        $this->assertMatchesRegularExpression($regexp, $result);
    }
}
