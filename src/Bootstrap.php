<?php

declare(strict_types=1);

namespace Zarthus\World\App;

use Zarthus\World\Container\Container;

final class Bootstrap
{
    public static function init(): void
    {
        $container = Container::create();

        $finder = \Symfony\Component\Finder\Finder::create()->in(__DIR__ . '/ServiceProvider');
        foreach ($finder as $item) {
            $classString = __NAMESPACE__ . "\\ServiceProvider\\{$item->getFilenameWithoutExtension()}";

            $container->addServiceProvider(new $classString());
        }
    }
}
