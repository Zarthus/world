<?php
declare(strict_types=1);

namespace Zarthus\World\Test\Framework;

use PHPUnit\Framework\TestCase;
use Zarthus\World\App\App;
use Zarthus\World\Container\Container;

class ContainerAwareTestCase extends TestCase
{
    private Container $container;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->container = App::getContainer();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
