<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Twig\Extension\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class BulmaBreadcrumbExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('breadcrumbs', fn (array $items): string => '', ['is_safe' => ['html']]),
            new TwigFunction('breadcrumbs_from_path', fn (string $path): string => '', ['is_safe' => ['html']]),
        ];
    }
}
