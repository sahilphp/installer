<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Exercises the executable declared in composer.json ("bin": ["bin/sahilphp"]).
 */
final class BinaryTest extends TestCase
{
    public function testListShowsDoctorAndNew(): void
    {
        $process = $this->runBinary(['list']);

        $output = $process->getOutput().$process->getErrorOutput();

        self::assertSame(0, $process->getExitCode(), $output);
        self::assertStringContainsString('doctor', $output);
        self::assertStringContainsString('new', $output);
    }

    public function testHelpListsDoctorAndNew(): void
    {
        $process = $this->runBinary(['--help']);

        $output = $process->getOutput().$process->getErrorOutput();

        self::assertSame(0, $process->getExitCode(), $output);
        self::assertStringContainsString('doctor', $output);
        self::assertStringContainsString('new', $output);
    }

    public function testDoctorRunsInsteadOfThrowingCommandNotFound(): void
    {
        $process = $this->runBinary(['doctor']);

        $output = $process->getOutput().$process->getErrorOutput();

        self::assertStringNotContainsString('CommandNotFoundException', $output);
        self::assertStringNotContainsString('Command "doctor" is not defined', $output);
        self::assertStringContainsString('SahilPHP Installer diagnostics', $output);
        self::assertContains($process->getExitCode(), [0, 1], $output);
    }

    /**
     * @param list<string> $arguments
     */
    private function runBinary(array $arguments): Process
    {
        $process = new Process([PHP_BINARY, 'bin/sahilphp', ...$arguments], dirname(__DIR__));
        $process->setTimeout(120.0);
        $process->run();

        return $process;
    }
}
