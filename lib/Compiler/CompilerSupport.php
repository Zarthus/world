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
        private readonly bool $allowOutsideAccess = false,
        private readonly bool $prohibitDirectoryTraversalBack = true,
    ) {
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        if (!$this->validate($options, $template)) {
            return false;
        }

        if (null !== $template) {
            foreach ($this->extensions as $extension) {
                $normalizedExtension = '.' . trim($extension, '.');

                if (str_ends_with($template, $normalizedExtension)) {
                    return true;
                }
            }
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
        if ($this->prohibitDirectoryTraversalBack && null !== $template && str_contains($template, '..')) {
            return false;
        }

        if ($this->allowOutsideAccess && $options->isLiveCompilation()) {
            return true;
        }

        $in = $options->getInDirectory();
        if (!is_dir($in)) {
            return false;
        }
        return true;
    }
}
