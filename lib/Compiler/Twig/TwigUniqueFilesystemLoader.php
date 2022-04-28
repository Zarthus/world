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
            $templateHash = hash_file('md5', $template);
            if (false === $templateHash) {
                $templateHash = 'miss';
            }
        } else {
            $templateHash = 'miss';
        }

        return $templateHash . parent::getCacheKey($name);
    }
}
