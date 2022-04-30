<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Twig\Extension;

use Twig\Extension\ExtensionInterface;

interface TwigExtensionProviderInterface
{
    /** @return list<ExtensionInterface> */
    public function getExtensions(): array;
}
