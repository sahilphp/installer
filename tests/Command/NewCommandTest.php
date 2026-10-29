<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests\Command;

use PHPUnit\Framework\TestCase;
use SahilPHP\Installer\Command\NewCommand;
use SahilPHP\Installer\Service\ProjectCreator;
use SahilPHP\Installer\Ui\UiChoice;
use Symfony\Component\Console\Tester\CommandTester;

final class NewCommandTest extends TestCase
{
    public function testCommandAcceptsPositionalProjectName(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '',
                $this->isInstanceOf(UiChoice::class),
                $this->anything(),
                true
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui' => 'tailwind',
        ], ['interactive' => false]);

        self::assertSame(0, $exitCode);
    }

    public function testCommandRejectsInvalidProjectName(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->never())->method('create');

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'invalid name',
            '--ui' => 'tailwind',
        ], ['interactive' => false]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Invalid project name', $tester->getDisplay());
    }

    public function testCommandRejectsInvalidUiOption(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->never())->method('create');

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui' => 'invalid-ui',
        ], ['interactive' => false]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Invalid UI framework', $tester->getDisplay());
    }

    public function testCommandAcceptsTailwindUi(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '',
                $this->callback(fn ($ui) => $ui->value === 'tailwind'),
                $this->anything(),
                true
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui' => 'tailwind',
        ], ['interactive' => false]);

        self::assertSame(0, $exitCode);
    }

    public function testCommandAcceptsBootstrapUi(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '',
                $this->callback(fn ($ui) => $ui->value === 'bootstrap'),
                $this->anything(),
                true
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui' => 'bootstrap',
        ], ['interactive' => false]);

        self::assertSame(0, $exitCode);
    }

    public function testCommandSupportsNoDepsOption(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '',
                $this->isInstanceOf(UiChoice::class),
                $this->anything(),
                false
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui' => 'tailwind',
            '--no-deps' => true,
        ], ['interactive' => false]);

        self::assertSame(0, $exitCode);
    }

    public function testCommandSupportsDirectoryOption(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '/custom/path',
                $this->isInstanceOf(UiChoice::class),
                $this->anything(),
                true
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui' => 'tailwind',
            '--dir' => '/custom/path',
        ], ['interactive' => false]);

        self::assertSame(0, $exitCode);
    }

    public function testCommandReturnsFailureWhenCreatorFails(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->willReturn(false);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui' => 'tailwind',
        ], ['interactive' => false]);

        self::assertSame(1, $exitCode);
    }
}
