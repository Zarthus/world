<?php

declare(strict_types=1);

namespace Zarthus\World\App\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\Compilers\AssetCompiler;
use Zarthus\World\Compiler\Compilers\DebugCompiler;
use Zarthus\World\Compiler\Compilers\GroupCompiler;
use Zarthus\World\Compiler\Compilers\JsonCompiler;
use Zarthus\World\Compiler\Compilers\MarkdownCompiler;
use Zarthus\World\Compiler\Compilers\NoneCompiler;
use Zarthus\World\Compiler\Compilers\SassCompiler;
use Zarthus\World\Compiler\Compilers\TwigCompiler;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;

final class CompilerServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return CompilerInterface::class === $id || GroupCompiler::class === $id;
    }

    public function register(): void
    {
        $object = new ObjectArgument(new GroupCompiler([
            $this->getContainer()->get(DebugCompiler::class),
            $this->getContainer()->get(AssetCompiler::class),
            $this->getContainer()->get(SassCompiler::class),
            $this->getContainer()->get(MarkdownCompiler::class),
            $this->getContainer()->get(TwigCompiler::class),
            $this->getContainer()->get(JsonCompiler::class),
            $this->getContainer()->get(NoneCompiler::class),
        ]));

        $this->getContainer()->add(CompilerInterface::class, $object);
        $this->getContainer()->add(GroupCompiler::class, $object);
    }
}
