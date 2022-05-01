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
            new TwigFunction('route', fn (string $asset): string => $this->environment->getString(EnvVar::HttpBaseDir) . ltrim($asset, '/')),
            new TwigFunction('asset', fn (string $asset): string => $this->environment->getString(EnvVar::HttpBaseDir) . ltrim($asset, '/')),
        ];
    }
}
