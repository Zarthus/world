<?php

declare(strict_types=1);

namespace Zarthus\World\App\File;

use Zarthus\World\File\DirectoryMappingInterface;

final class DirectoryMappingResolver implements DirectoryMappingInterface
{
    /**
     * @param array $config
     *
     * @psalm-param array{autoresolve: array<string, string>, replace: array<string, string>, fallback: string} $config
     *
     * @param array<string, string> $cache
     */
    public function __construct(
        private readonly array $config,
        private array $cache = [],
    ) {
    }

    public function resolveDirectory(string $path): string
    {
        if (null !== $cache = $this->loadCache('dir', $path)) {
            return $cache;
        }

        $firstDirectory = strtok($path, '/');

        foreach ($this->config['autoresolve'] as $matcher) {
            if ($firstDirectory === $matcher) {
                return $this->cache($this->createSignature('dir', $path), $matcher);
            }
        }

        foreach ($this->config['replace'] as $matcher => $replacement) {
            if ($firstDirectory === $matcher) {
                return $this->cache($this->createSignature('dir', $path), $replacement);
            }
        }

        return $this->cache($this->createSignature('dir', $path), $this->config['fallback']);
    }

    public function resolveFilePath(string $path): string
    {
        if (null !== $cache = $this->loadCache('file', $path)) {
            return $cache;
        }

        $directory = $this->resolveDirectory($path);
        if ('html' !== $directory) {
            $result = ltrim(preg_replace('@^/?' . preg_quote($directory, '@') . '/?@', '', $path), '/');
        } else {
            $result = ltrim($path, '/');
        }

        if (empty($result)) {
            $result = '/';
        }

        return $this->cache($this->createSignature('file', $path), $result);
    }

    private function createSignature(string $method, string $path): string
    {
        return "$method|$path";
    }

    private function loadCache(string $method, string $path): ?string
    {
        return $this->cache[$this->createSignature($method, $path)] ?? null;
    }

    private function cache(string $signature, string $result): string
    {
        $this->cache[$signature] = $result;

        return $result;
    }
}
