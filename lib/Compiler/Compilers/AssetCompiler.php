<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler\Compilers;

use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\Compiler\CompileResult;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\CompilerSupport;
use Zarthus\World\Compiler\CompileType;
use Zarthus\World\Container\Container;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Exception\TemplateIllegalException;
use Zarthus\World\Exception\TemplateNotFoundException;

/**
 * The major difference between this and {@see NoneCompiler} is that this one knows specifically
 * what assets are and what it supports - should be load first as its whitelist is smaller.
 *
 * NoneCompiler just supports everything that exists but doesn't know what they are and thus should be load last.
 */
final class AssetCompiler implements CompilerInterface
{
    use LogAwareTrait;

    private readonly CompilerSupport $compilerSupport;

    public function __construct(
        private readonly Container $container,
        private readonly Environment $environment,
    ) {
        $this->compilerSupport = new CompilerSupport([''], ['png', 'jpg', 'ico', 'map', 'woff', 'woff2']);
    }

    public function supports(CompilerOptions $options, ?string $template): bool
    {
        if (null === $template) {
            return false;
        }
        return $this->compilerSupport->supports($options, $template);
    }

    public function compile(CompilerOptions $options): void
    {
        // This compiler does not do anything.
    }

    public function compileTemplate(CompilerOptions $options, string $template): void
    {
        // This compiler does not do anything.
    }

    public function renderTemplate(CompilerOptions $options, string $template): CompileResult
    {
        if (!$this->supports($options, $template)) {
            throw new TemplateIllegalException($template, self::class);
        }

        $asset = $options->getOutDirectory() . '/' . $template;
        if (!file_exists($asset)) {
            throw new TemplateNotFoundException($template, $options, self::class);
        }

        $this->getLogger()->debug("Successfully resolved asset: $asset");

        return new CompileResult(
            CompileType::Asset,
            file_get_contents($asset),
            mime_content_type($asset),
        );
    }
}
