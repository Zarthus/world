<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Zarthus\World\Environment\Development;
use Zarthus\World\Environment\EnvironmentInterface;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Environment\Pages;
use Zarthus\World\Environment\Production;
use Zarthus\World\Environment\Tests;

final class EnvironmentTest extends TestCase
{
    /**
     * @psalm-suppress InternalClass
     * @return list<EnvironmentInterface[]>
     */
    public function dataEnvironment(): array
    {
        return [
            [new Development()],
            [new Production()],
            [new Pages()],
            [new Tests()],
        ];
    }

    /**
     * @dataProvider dataEnvironment
     * @psalm-suppress InternalMethod
     * @psalm-suppress InternalClass
     */
    public function testEnvironment(EnvironmentInterface $environment): void
    {
        foreach (EnvVar::cases() as $item) {
            $expectedType = $this->getTypeMatcher($item);

            $result = $environment->get($item);

            $nullable = str_starts_with($expectedType, '?');
            if ($nullable && is_null($result)) {
                /** @psalm-suppress RedundantCondition */
                $this->assertNull($result);
                return;
            }

            $this->assertThat($result, $this->isType(ltrim($expectedType, '?')), "Environment Variable: " . $item->name);
        }
    }

    private function getTypeMatcher(EnvVar $var): string
    {
        return match ($var) {
            EnvVar::Name, EnvVar::LogLevel => 'string',
            EnvVar::Development, EnvVar::Compress, EnvVar::Sass => 'bool',
            EnvVar::HttpListeners => 'array',
            EnvVar::HttpBaseDir, EnvVar::HttpCertificatePath => '?string',
            default => $this->fail('Did not resolve env: ' . $var->name),
        };
    }
}
