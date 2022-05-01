<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Twig\Extension\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class BootstrapIconExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('bsi_name', fn (string $name): string => "bi-$name"),
            new TwigFunction('bsi', fn (string $name): string => "<i class=\"bi-$name\"></i>", ['is_safe' => ['html']]),
        ];
    }
}
