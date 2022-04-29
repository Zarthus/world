<?php

declare(strict_types=1);

namespace Zarthus\World\App\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Zarthus\World\App\File\DirectoryMappingResolver;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;
use Zarthus\World\File\DirectoryMappingInterface;

final class DirectoryMappingProvider extends AbstractServiceProvider
{
    private const CONFIGURATION = [
        'autoresolve' => [
            'api',
            'assets',
            'articles',
            'css',
            'javascript',
            'style',
            'html',
            'vendor',
        ],
        'replace' => [
            '/' => 'html',
            'errors' => 'html',
        ],
        'fallback' => 'html',
    ];

    public function provides(string $id): bool
    {
        return DirectoryMappingInterface::class === $id;
    }

    /** @psalm-suppress InvalidArgument */
    public function register(): void
    {
        $resolver = new DirectoryMappingResolver(self::CONFIGURATION);
        $this->container->add(DirectoryMappingInterface::class, new ObjectArgument($resolver));
    }
}
