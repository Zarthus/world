<?php
declare(strict_types=1);

namespace Zarthus\World\File;

final class MimeTypeResolver
{
    /** @param array<string, string> $mappings */
    public function __construct(
        private readonly array $mappings
    ) {
    }

    public function resolve(string $path): string
    {
        $file = basename($path);
        $extension = preg_replace('@^[^.]+\.@', '', $file);
        if (!empty($extension) && isset($this->mappings[$extension])) {
            return $this->mappings[$extension];
        }

        $mimeType = mime_content_type($path);
        if (false === $mimeType) {
            return 'text/plain';
        }
        return $mimeType;
    }
}
