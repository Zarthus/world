<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Framework;

use PHPUnit\Framework\TestCase;
use Zarthus\World\App\App;
use Zarthus\World\Container\Container;

class ContainerAwareTestCase extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = App::getContainer();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
