<?php

declare(strict_types=1);

namespace Zarthus\World\App\File;

use Zarthus\World\File\MimeTypeResolverInterface;

final class MimeTypeResolver implements MimeTypeResolverInterface
{
    /** @param array<string, string> $mappings */
    public function __construct(
        private readonly array $mappings,
    ) {
    }

    public function resolve(string $path): string
    {
        $file = basename($path);
        $extension = preg_replace('@^[^.]+\.(?:min\.)?@', '', $file);

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
