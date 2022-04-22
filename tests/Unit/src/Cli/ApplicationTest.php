<?php

declare(strict_types=1);

namespace Zarthus\World\Test\Unit\App\Cli;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application as SymfonyApplication;
use Zarthus\World\App\App;
use Zarthus\World\App\Cli\Application;
use Zarthus\World\Command\CommandResult;
use Zarthus\World\Exception\FatalAppException;
use Zarthus\World\Test\Lib\Cli\ExceptionCommand;
use Zarthus\World\Test\Lib\Cli\TestCommand;

final class ApplicationTest extends TestCase
{
    private SymfonyApplication $sfApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sfApplication = new SymfonyApplication('UnitTest', '1.0');
        $this->sfApplication->setCatchExceptions(false);
        $this->sfApplication->setAutoExit(false);
    }

    public function testOk(): void
    {
        $testCommand = $this->getMockBuilder(TestCommand::class)
            ->setMockClassName('TestACommand')
            ->onlyMethods(['execute', 'configure'])
            ->getMock();
        $testCommand->expects($this->once())->method('execute')->willReturn(CommandResult::Ok);
        $testCommand->expects($this->once())->method('configure');

        $app = new Application($this->sfApplication, [$testCommand]);
        $app->exec(['app.php', 'test:a']);
    }

    public function testError(): void
    {
        $testCommand = $this->getMockBuilder(TestCommand::class)
            ->setMockClassName('TestBCommand')
            ->onlyMethods(['execute', 'configure'])
            ->getMock();
        $testCommand->expects($this->once())->method('execute')->willReturn(CommandResult::Error);
        $testCommand->expects($this->once())->method('configure');

        $app = new Application($this->sfApplication, [$testCommand]);
        $app->exec(['app.php', 'test:b']);
    }

    public function testOkWithoutMocking(): void
    {
        $app = new Application($this->sfApplication, [new TestCommand()]);
        $app->exec(['app.php', 'test']);
        $this->assertTrue(true);
    }

    public function testException(): void
    {
        $this->expectException(FatalAppException::class);
        $this->expectExceptionMessage('Mock error!');

        $app = new Application($this->sfApplication, [new ExceptionCommand()]);
        $app->exec(['app.php', 'exception']);
    }
}
