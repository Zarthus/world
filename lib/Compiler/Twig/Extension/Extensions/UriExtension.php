<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Twig\Extension\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;

final class UriExtension extends AbstractExtension
{
    public function __construct(
        private readonly Environment $environment,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', fn (string $asset): string => $this->environment->getString(EnvVar::HttpBaseDir) . $asset),
            new TwigFunction('path', fn (string $path): string => $this->environment->getString(EnvVar::HttpBaseDir) . $path),
            new TwigFunction('url', fn (string $url): string => $this->environment->getString(EnvVar::HttpBaseDir) . $url),

            new TwigFunction('link', fn (string $url): string => $this->environment->getString(EnvVar::HttpBaseDir) . $url),
            new TwigFunction('css', fn (string $url): string => $this->environment->getString(EnvVar::HttpBaseDir) . $url),
            new TwigFunction('js', fn (string $url): string => $this->environment->getString(EnvVar::HttpBaseDir) . $url),
        ];
    }
}
