<?php

declare(strict_types=1);

namespace Zarthus\World\App\Twig;

use Twig\Extension\ExtensionInterface;
use Zarthus\World\Compiler\Twig\Extension\TwigExtensionProviderInterface;

final class TwigExtensionRetriever implements TwigExtensionProviderInterface
{
    /** @param list<ExtensionInterface> $extensions */
    public function __construct(
        private readonly array $extensions,
    ) {
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }
}
