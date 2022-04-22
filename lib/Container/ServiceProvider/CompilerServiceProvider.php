<?php

declare(strict_types=1);

namespace Zarthus\World\Container\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\GroupCompiler;
use Zarthus\World\Compiler\JsonCompiler;
use Zarthus\World\Compiler\MarkdownCompiler;
use Zarthus\World\Compiler\NoneCompiler;
use Zarthus\World\Compiler\SassCompiler;
use Zarthus\World\Compiler\TwigCompiler;

final class CompilerServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return CompilerInterface::class === $id;
    }

    public function register(): void
    {
        $this->getContainer()->add(CompilerInterface::class, new ObjectArgument(new GroupCompiler([
            $this->getContainer()->get(JsonCompiler::class),
            $this->getContainer()->get(SassCompiler::class),
            $this->getContainer()->get(MarkdownCompiler::class),
            $this->getContainer()->get(TwigCompiler::class),
            $this->getContainer()->get(NoneCompiler::class),
        ])));
    }
}
