<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Twig;

use Twig\Loader\FilesystemLoader;

/**
 * In "compiling the same file but it has different content" context, twig returns the old contents.
 * This helps twig generate unique keys.
 *
 * @TODO migrate to new project and includable dependency
 *
 * @link https://github.com/twigphp/Twig/issues/3693
 */
final class TwigUniqueFilesystemLoader extends FilesystemLoader
{
    public function getCacheKey(string $name): string
    {
        $template = $this->findTemplate($name, false);
        if (null !== $template) {
            $templateHash = filemtime($template);
            if (false === $templateHash) {
                $templateHash = 1_600_000_000;
            }
        } else {
            $templateHash = 1_600_000_000;
        }

        return dechex(abs($templateHash - 1_600_000_000)) . parent::getCacheKey($name);
    }
}
