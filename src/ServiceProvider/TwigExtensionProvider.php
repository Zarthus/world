<?php

declare(strict_types=1);

namespace Zarthus\World\App\ServiceProvider;

use League\Container\Argument\Literal\ObjectArgument;
use Zarthus\World\App\Twig\Extensions\SvgIconExtension;
use Zarthus\World\App\Twig\Extensions\WorldExtension;
use Zarthus\World\App\Twig\TwigExtensionRetriever;
use Zarthus\World\Compiler\Twig\Extension\Extensions\ArticleExtension;
use Zarthus\World\Compiler\Twig\Extension\Extensions\BootstrapIconExtension;
use Zarthus\World\Compiler\Twig\Extension\Extensions\BulmaBreadcrumbExtension;
use Zarthus\World\Compiler\Twig\Extension\Extensions\UriExtension;
use Zarthus\World\Compiler\Twig\Extension\TwigExtensionProviderInterface;
use Zarthus\World\Container\ServiceProvider\AbstractServiceProvider;

final class TwigExtensionProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return TwigExtensionProviderInterface::class === $id;
    }

    public function register(): void
    {
        $object = new ObjectArgument(new TwigExtensionRetriever([
            $this->getContainer()->get(UriExtension::class),
            $this->getContainer()->get(ArticleExtension::class),
            $this->getContainer()->get(BulmaBreadcrumbExtension::class),
            $this->getContainer()->get(BootstrapIconExtension::class),
            $this->getContainer()->get(WorldExtension::class),
            $this->getContainer()->get(SvgIconExtension::class),
        ]));

        $this->getContainer()->add(TwigExtensionProviderInterface::class, $object);
    }
}
