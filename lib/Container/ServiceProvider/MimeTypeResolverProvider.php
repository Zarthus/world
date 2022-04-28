<?php

declare(strict_types=1);

namespace Zarthus\World\Container\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Zarthus\World\File\MimeTypeResolver;

final class MimeTypeResolverProvider extends AbstractServiceProvider
{
    private const MAPPINGS = [
        'text/css' => ['css', 'scss', 'sass'],
        'text/html' => ['html', 'twig', 'md'],
        'application/json' => ['json'],
    ];

    public function provides(string $id): bool
    {
        return MimeTypeResolver::class === $id;
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
        $this->container->add(MimeTypeResolver::class, new ObjectArgument($resolver));
    }
}
