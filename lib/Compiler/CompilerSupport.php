<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

final class CompilerSupport
{
    /**
     * @param string[] $relativeDirectories
     * @param string[] $extensions
     * @param bool $prohibitDirectoryTraversalBack Prohibit directories like ".."
     */
    public function __construct(
        private readonly array $relativeDirectories,
        private readonly array $extensions,
        private readonly array $allowDirectoryCollisions = [],
        private readonly bool $prohibitDirectoryTraversalBack = true,
    ) {
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        if (!$this->validate($options, $template)) {
            return false;
        }

        if ($template !== null) {
            foreach ($this->extensions as $extension) {
                $normalizedExtension = '.' . trim($extension, '.');

                if (str_ends_with($template, $normalizedExtension)) {
                    return true;
                }
            }
            return false;
        }

        foreach ($this->relativeDirectories as $directory) {
            $normalizedDirectory = '/' . trim($directory, '/');

            if (str_contains($options->getInDirectory(), $normalizedDirectory) ||
                str_contains($options->getOutDirectory(), $normalizedDirectory)) {
                return true;
            }
        }

        return false;
    }

    private function validate(CompilerOptions $options, ?string $template): bool
    {
        $in = $options->getInDirectory();


        if (!is_dir($in)) {
            return false;
        }

        if ($this->prohibitDirectoryTraversalBack && $template !== null && str_contains($template, '..')) {
            return false;
        }

        return true;
    }
}
