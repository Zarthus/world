<?php

declare(strict_types=1);

namespace Zarthus\World\Test\System\App\File;

use Zarthus\World\File\DirectoryMappingInterface;
use Zarthus\World\Test\Framework\ContainerAwareTestCase;

final class DirectoryMapperTest extends ContainerAwareTestCase
{
    /** @return list<list<string>> */
    public function dataPaths(): array
    {
        return [
            // Known patterns
            ['api', 'info.json', '/api/info.json'],
            ['api', 'info.json.twig', '/api/info.json.twig'],

            ['articles', 'foo.html', '/articles/foo.html'],

            ['assets', 'fonts/bootstrap-icons.woff', '/assets/fonts/bootstrap-icons.woff'],

            ['javascript', 'theme-switcher.js', '/javascript/theme-switcher.js'],
            ['javascript', 'some/script.js', '/javascript/some/script.js'],

            ['style', 'icons.css', '/style/icons.css'],

            ['vendor', 'some/file/deep/some.min.js', '/vendor/some/file/deep/some.min.js'],

            // HTML patterns and fallbacks
            ['html', '/', '/'],
            ['html', 'index.twig', '/index.twig'],
            ['html', 'favicon.ico', '/favicon.ico'],
            ['html', 'favicon-16x16.png', '/favicon-16x16.png'],
            ['html', 'errors/404.html', '/errors/404.html'],

            // Nonexistent (fallbacks)
            ['html', 'foo', '/foo'],
            ['html', 'foo/bar/baz.foo', '/foo/bar/baz.foo'],
        ];
    }


    /**
     * @dataProvider dataPaths
     * @covers \Zarthus\World\App\File\DirectoryMappingResolver
     * @covers \Zarthus\World\App\ServiceProvider\DirectoryMappingProvider
     */
    public function testResolver(string $expectationDir, string $expectationFile, string $requestPath): void
    {
        $mapper = $this->getContainer()->get(DirectoryMappingInterface::class);

        $this->assertSame($expectationDir, $mapper->resolveDirectory($requestPath), 'dir');
        $this->assertSame($expectationFile, $mapper->resolveFilePath($requestPath), 'file');
    }
}
