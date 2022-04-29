<?php

declare(strict_types=1);

namespace Zarthus\World\App\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Zarthus\World\App\File\MimeTypeResolver;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;
use Zarthus\World\File\MimeTypeResolverInterface;

final class MimeTypeResolverProvider extends AbstractServiceProvider
{
    private const MAPPINGS = [
        'text/css' => ['css', 'scss', 'sass'],
        'text/html' => ['html', 'twig', 'md'],
        'text/javascript' => ['js'],
        'application/json' => ['json', 'json.twig'],
        'image/x-icon' => ['ico'],
    ];

    public function provides(string $id): bool
    {
        return MimeTypeResolverInterface::class === $id;
    }

    public function register(): void
    {
        $mappings = [];
        foreach (self::MAPPINGS as $mimeType => $extensions) {
            foreach ($extensions as $extension) {
                $mappings[$extension] = $mimeType;
            }
        }

        $resolver = new MimeTypeResolver($mappings);
        $this->container->add(MimeTypeResolverInterface::class, new ObjectArgument($resolver));
    }
}
