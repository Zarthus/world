<?php
declare(strict_types=1);

namespace Zarthus\World\Test\Framework;

use Zarthus\World\Test\Framework\Exception\FixtureNotFound;

trait LoadsFixtures
{
    private function fixture(string $name): string
    {
        $fixture = __DIR__ . '/../Fixtures/' . $name;
        if (!file_exists($fixture)) {
            throw new FixtureNotFound($name);
        }
        return $fixture;
    }

    private function loadFixture(string $name): string
    {
        return file_get_contents($this->fixture($name));
    }
}
