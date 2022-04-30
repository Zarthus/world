<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Twig\Extension\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ArticleExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('list_articles', fn (): array => []),
            new TwigFunction('load_article', fn (string $name): string => ''),
        ];
    }
}
