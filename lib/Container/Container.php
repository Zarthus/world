<?php

declare(strict_types=1);

namespace Zarthus\World\Container;

use League\Container\Argument\Literal\ObjectArgument;
use League\Container\Container as LeagueContainer;
use League\Container\ReflectionContainer;
use Psr\Container\ContainerInterface;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;
use Zarthus\World\Container\ServiceProvider\CompilerServiceProvider;
use Zarthus\World\Container\ServiceProvider\EnvironmentServiceProvider;
use Zarthus\World\Container\ServiceProvider\FilePathProvider;
use Zarthus\World\Container\ServiceProvider\LoggerServiceProvider;
use Zarthus\World\Container\ServiceProvider\SassServiceProvider;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\SingletonException;

final class Container implements ContainerInterface
{
    private LeagueContainer $container;

    private static ?self $self = null;

    private function __construct()
    {
        if (null !== self::$self) {
            throw new SingletonException();
        }
        $this->init();
    }

    public static function create(): Container
    {
        if (null === self::$self) {
            self::$self = new self();
        }

        return self::$self;
    }

    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function get(string $id)
    {
        return $this->container->get($id);
    }

    /**
     * @param class-string $id
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * @psalm-internal Zarthus\World\Attribute
     * @private
     */
    public function addServiceProvider(AbstractServiceProvider $serviceProvider): void
    {
        $this->container->addServiceProvider($serviceProvider);
    }

    private function init(): void
    {
        $container = new LeagueContainer();
        $container->addServiceProvider(new FilePathProvider());
        $container->addServiceProvider(new EnvironmentServiceProvider());
        $container->addServiceProvider(new LoggerServiceProvider());
        $container->add(self::class, new ObjectArgument($this));

        $environment = $container->get(Environment::class);
        $container->delegate(new ReflectionContainer(!$environment->getBool(EnvVar::Development)));

        // Add extra service providers
        $container->addServiceProvider(new SassServiceProvider());
        $container->addServiceProvider(new CompilerServiceProvider());

        $this->container = $container;
    }
}
