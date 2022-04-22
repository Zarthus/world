<?php

declare(strict_types=1);

namespace Zarthus\World\Container\ServiceProvider;

use League\Container\ContainerAwareTrait;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * @property DefinitionContainerInterface $container
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    protected string $identifier;

    final public function __construct()
    {
        $this->identifier = static::class;
    }

    final public function getIdentifier(): string
    {
        return $this->identifier;
    }

    final public function setIdentifier(string $id): ServiceProviderInterface
    {
        $this->identifier = $id;
        return $this;
    }
}
