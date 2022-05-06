<?php

declare(strict_types=1);

namespace Zarthus\World\App\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;

final class SvgIconExtension extends AbstractExtension
{
    use LogAwareTrait;

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'icon',
                /** @param string[] $classes */
                fn (string $namespace, string $name, array $classes = []): string => $this->svg("game-icons/$namespace", $name, array_merge(['icon-game'], $classes)),
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'svg',
                /** @param string[] $classes */
                fn (string $namespace, string $name, array $classes = []): string => $this->svg($namespace, $name, $classes),
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param string[] $classes
     */
    private function svg(string $namespace, string $name, array $classes): string
    {
        $canonicalPath = "/assets/svg/$namespace/$name.svg";
        $path = Path::www(true) . $canonicalPath;

        if (!file_exists($path)) {
            $this->getLogger()->error("Missing SVG: $canonicalPath", ['path' => $path]);
            return '';
        }

        $result = file_get_contents($path);
        if (false === $result) {
            $this->getLogger()->error("False result when loading SVG: $canonicalPath", ['path' => $path]);
            return '';
        }

        return $this->processSvg($result, $classes);
    }

    /**
     * @param string[] $classes
     */
    private function processSvg(string $svg, array $classes): string
    {
        $classList = implode(' ', $classes);
        return str_replace('<svg ', '<svg class="' . $classList . '" ', $svg);
    }

    private function getLoggerName(): string
    {
        return 'SVG';
    }
}
