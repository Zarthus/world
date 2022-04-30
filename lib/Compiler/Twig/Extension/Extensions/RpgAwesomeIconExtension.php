<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Twig\Extension\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RpgAwesomeIconExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('rpgicon_name', fn (string $name): string => "ra-$name"),
            new TwigFunction('rpgicon', fn (string $name): string => "<i class=\"ra-$name\"></i>", ['is_safe' => ['html']]),
        ];
    }
}
