<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Twig\Extension\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FontAwesomeIconExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('fas', fn (string $name): string => "<i class=\"fa-solid fa-$name\"></i>", ['is_safe' => ['html']]),
            new TwigFunction('fab', fn (string $name): string => "<i class=\"fa-brand fa-$name\"></i>", ['is_safe' => ['html']]),
            new TwigFunction('fa', fn (string $name): string => "<i class=\"fa-regular fa-$name\"></i>", ['is_safe' => ['html']]),
        ];
    }
}
