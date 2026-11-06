<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests\Command;

use PHPUnit\Framework\TestCase;
use SahilPHP\Installer\Command\NewCommand;
use SahilPHP\Installer\Service\ProjectCreator;
use SahilPHP\Installer\Ui\UiChoice;
use SahilPHP\Installer\Ui\UiKit;
use Symfony\Component\Console\Tester\CommandTester;

final class NewCommandTest extends TestCase
{
    public function testCommandAcceptsPositionalProjectNameAndBothOptions(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '',
                UiKit::React,
                $this->callback(fn (UiChoice $css): bool => $css->value === 'bootstrap'),
                $this->anything(),
                true
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui-kit' => 'react',
            '--css' => 'bootstrap',
        ], ['interactive' => false]);

        self::assertSame(0, $exitCode);
    }

    public function testNonInteractiveModeRequiresCssFramework(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->never())->method('create');

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['name' => 'test-app'], ['interactive' => false]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('CSS framework is required', $tester->getDisplay());
    }

    public function testNonInteractiveModeUsesSelectedUiKitAndCss(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '',
                UiKit::None,
                $this->callback(fn (UiChoice $css): bool => $css->value === 'tailwind'),
                $this->anything(),
                true
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(
            ['name' => 'test-app', '--css' => 'tailwind'],
            ['interactive' => false]
        );

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Sahil Engine', $tester->getDisplay());
    }

    public function testInteractiveModeAsksBothQuestionsInOrder(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '',
                UiKit::Vue,
                $this->callback(fn (UiChoice $css): bool => $css->value === 'tailwind'),
                $this->anything(),
                true
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);
        $tester->setInputs(['3', '1']);

        $exitCode = $tester->execute(['name' => 'test-app'], ['interactive' => true]);

        self::assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('Choose your UI kit:', $display);
        self::assertStringContainsString('Choose your CSS framework:', $display);
        self::assertLessThan(
            strpos($display, 'Choose your CSS framework:'),
            strpos($display, 'Choose your UI kit:'),
        );
    }

    public function testInteractiveModeAcceptsUiKitAndCssNames(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '',
                UiKit::Svelte,
                $this->callback(fn (UiChoice $css): bool => $css->value === 'bootstrap'),
                $this->anything(),
                true
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);
        $tester->setInputs(['svelte', 'bootstrap']);

        $exitCode = $tester->execute(['name' => 'test-app'], ['interactive' => true]);

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
            '--ui-kit' => 'none',
            '--css' => 'tailwind',
        ], ['interactive' => false]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Invalid project name', $tester->getDisplay());
    }

    public function testCommandRejectsInvalidUiKitOption(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->never())->method('create');

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui-kit' => 'angular',
            '--css' => 'tailwind',
        ], ['interactive' => false]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Invalid UI kit', $tester->getDisplay());
    }

    public function testCommandRejectsInvalidCssOption(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->never())->method('create');

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui-kit' => 'none',
            '--css' => 'bulma',
        ], ['interactive' => false]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Invalid CSS framework', $tester->getDisplay());
    }

    public function testUiOptionIsAliasForUiKit(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '',
                UiKit::React,
                $this->callback(fn (UiChoice $css): bool => $css->value === 'bootstrap'),
                $this->anything(),
                true
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui' => 'react',
            '--css' => 'bootstrap',
        ], ['interactive' => false]);

        self::assertSame(0, $exitCode);
    }

    public function testUiPackageNameIsRejectedWithGuidance(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->never())->method('create');

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'sahilphp/firefiber',
            '--ui-kit' => 'firefiber',
            '--css' => 'tailwind',
        ], ['interactive' => false]);

        self::assertSame(1, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('"sahilphp/firefiber" looks like a SahilPHP UI package', $display);
        self::assertStringContainsString('Did you mean: sahilphp new my-app ?', $display);
    }

    public function testCommandSupportsNoDepsAndDirectoryOptions(): void
    {
        $creator = $this->createMock(ProjectCreator::class);
        $creator->expects($this->once())
            ->method('create')
            ->with(
                'test-app',
                '/custom/path',
                UiKit::None,
                $this->isInstanceOf(UiChoice::class),
                $this->anything(),
                false
            )
            ->willReturn(true);

        $command = new NewCommand($creator);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'test-app',
            '--ui-kit' => 'none',
            '--css' => 'tailwind',
            '--dir' => '/custom/path',
            '--no-deps' => true,
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
            '--ui-kit' => 'none',
            '--css' => 'tailwind',
        ], ['interactive' => false]);

        self::assertSame(1, $exitCode);
    }
}
