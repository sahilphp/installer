<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests;

use PHPUnit\Framework\TestCase;
use SahilPHP\Installer\Application;
use SahilPHP\Installer\Command\DoctorCommand;
use SahilPHP\Installer\Command\NewCommand;
use Symfony\Component\Console\Tester\ApplicationTester;

final class ApplicationTest extends TestCase
{
    public function testDoctorCommandIsRegistered(): void
    {
        self::assertTrue((new Application())->has('doctor'));
    }

    public function testDoctorCommandCanBeResolvedByItsName(): void
    {
        $command = (new Application())->find('doctor');

        self::assertInstanceOf(DoctorCommand::class, $command);
        self::assertSame('doctor', $command->getName());
    }

    public function testNewCommandIsStillRegistered(): void
    {
        $command = (new Application())->find('new');

        self::assertInstanceOf(NewCommand::class, $command);
        self::assertSame('new', $command->getName());
    }

    public function testListOutputIncludesEveryCommand(): void
    {
        $output = $this->runApplication(['command' => 'list']);

        self::assertStringContainsString('doctor', $output);
        self::assertStringContainsString('new', $output);
        self::assertStringNotContainsString('Command "doctor" is not defined', $output);
    }

    public function testHelpOutputIncludesEveryCommand(): void
    {
        $output = $this->runApplication(['--help' => true]);

        self::assertStringContainsString('doctor', $output);
        self::assertStringContainsString('new', $output);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runApplication(array $input): string
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $tester = new ApplicationTester($application);
        $exitCode = $tester->run($input);

        self::assertSame(0, $exitCode, $tester->getDisplay());

        return $tester->getDisplay();
    }
}
