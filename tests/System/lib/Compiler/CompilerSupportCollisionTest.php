<?php
declare(strict_types=1);

namespace Zarthus\World\Test\System\Compiler;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Zarthus\World\App\Path;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Compiler\Compilers\GroupCompiler;
use Zarthus\World\Test\Lib\ContainerAwareTestCase;

final class CompilerSupportCollisionTest extends ContainerAwareTestCase
{
    /**
     * Asserts that:
     * - For every compiler there is, it does not collide with other compilers on matches
     * - For every file or directory we have, there is at least one compiler that can handle it.
     */
    public function testThatAllCompilersHandleExactlyOneItem(): void
    {
        $finder = Finder::create()
            ->in(Path::lib() . '/Compiler/Compilers')
            ->notName('GroupCompiler.php')
            ->notName('NoneCompiler.php')
            ->files();
        $compilers = [];
        foreach ($finder as $compiler) {
            /** @var CompilerInterface $resolvedCompiler */
            $resolvedCompiler = $this->getContainer()->get(
                str_replace('GroupCompiler', $compiler->getFilenameWithoutExtension(), GroupCompiler::class)
            );
            $compilers[] = $resolvedCompiler;
        }

        $files = Finder::create()
            ->exclude(['assets', 'javascript'])
            ->in(Path::www(true));
        $collisions = [];
        foreach ($files as $path) {
            $relativeBaseDir = $this->getRelativeBaseDir($path->getRelativePathname());
            $options = $this->parseOptions($relativeBaseDir);
            $template = $this->parseTemplate($path, $relativeBaseDir);

            $supported = null;
            foreach ($compilers as $compiler) {
                $supports = $compiler->supports($options, $template);

                if ($supported && $supports) {
                    $collisions[] = sprintf("Both %s and %s support: (Directory: %s) (Template: %s)", $compiler::class, $supported, $relativeBaseDir ?? '/', $template ?? '(null)');
                }

                if ($supports) {
                    $supported = $compiler::class;
                }
            }
            if ($supported === null) {
                $collisions[] = sprintf("No compilers support: (Directory: %s) (Template: %s)", $relativeBaseDir ?? '/', $template ?? '(null)');
            }
        }

        $this->assertCount(0, $collisions, var_export($collisions, true));
    }

    private function parseOptions(string $relativeBaseDir): CompilerOptions
    {
        $options = new CompilerOptions(
            Path::www(true) . '/' .  $relativeBaseDir,
            Path::www(false) . '/' . $relativeBaseDir,
            false,
        );

        return $options;
    }

    private function parseTemplate(SplFileInfo $fileInfo, string $relativeBaseDir): ?string
    {
        $relativePath = $fileInfo->getRelativePathname();
        $bits = explode('/', $relativePath);
        $last = end($bits);

        if (str_contains($last, '.')) {
            $tpl = ltrim(str_replace($relativeBaseDir . '/', '', $relativePath), '/');
            return empty($tpl) ? null : $tpl;
        }
        return null;
    }

    private function getRelativeBaseDir(string $relativePathName): string
    {
        $count = substr_count($relativePathName, '/');
        return $count === 0 ? $relativePathName : dirname($relativePathName, $count);
    }
}
