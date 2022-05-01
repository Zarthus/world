<?php

declare(strict_types=1);

namespace Zarthus\World\App\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zarthus\World\Environment\Environment;

final class WorldExtension extends AbstractExtension
{
    public function __construct(
        private readonly Environment $environment,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('world_name', fn () => 'Novus'),
            new TwigFunction('world_author', fn () => 'Zarthus'),
        ];
    }
}
