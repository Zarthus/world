<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Framework\Exception;

final class FixtureNotFound extends \Exception
{
    public function __construct(string $fixture)
    {
        parent::__construct("Could not load fixture with name $fixture", 0, null);
    }
}
