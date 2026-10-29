<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests\Command;

use PHPUnit\Framework\TestCase;
use SahilPHP\Installer\Command\DoctorCommand;
use SahilPHP\Installer\Service\RequirementsChecker;
use Symfony\Component\Console\Tester\CommandTester;

final class DoctorCommandTest extends TestCase
{
    public function testHandlerRunsAndReportsEveryCheck(): void
    {
        $tester = new CommandTester(new DoctorCommand($this->checkerReturning([
            $this->row('php', RequirementsChecker::OK, 'PHP 8.4.0 is supported.'),
        ])));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('SahilPHP Installer diagnostics', $display);
        self::assertStringContainsString('[ OK ] PHP 8.4.0 is supported.', $display);
    }

    public function testMissingOptionalToolingIsAnActionableWarning(): void
    {
        $tester = new CommandTester(new DoctorCommand($this->checkerReturning([
            $this->row('php', RequirementsChecker::OK, 'PHP 8.4.0 is supported.'),
            $this->row(
                'node',
                RequirementsChecker::WARNING,
                'Node.js was not found on PATH - install Node.js 20 or newer from https://nodejs.org.'
            ),
        ])));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('[WARN]', $display);
        self::assertStringContainsString('Node.js 20 or newer', $display);
        self::assertStringContainsString('install Node.js', $display);
        self::assertStringNotContainsString('not defined', $display);
    }

    public function testFailedCheckReturnsNonZeroExitCodeAndKeepsActionableOutput(): void
    {
        $tester = new CommandTester(new DoctorCommand($this->checkerReturning([
            $this->row(
                'ext-mbstring',
                RequirementsChecker::ERROR,
                'The PHP extension "mbstring" is missing - install it, then run "sahilphp doctor" again.'
            ),
        ])));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('[FAIL]', $display);
        self::assertStringContainsString('mbstring', $display);
        self::assertStringContainsString('run "sahilphp doctor" again', $display);
    }

    public function testThrowingCheckerIsReportedInsteadOfEscaping(): void
    {
        $checker = new class extends RequirementsChecker {
            public function check(): array
            {
                throw new \RuntimeException('simulated probe failure');
            }
        };

        $tester = new CommandTester(new DoctorCommand($checker));
        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('simulated probe failure', $tester->getDisplay());
    }

    /**
     * @param list<array{name: string, status: string, detail: string}> $results
     */
    private function checkerReturning(array $results): RequirementsChecker
    {
        return new class($results) extends RequirementsChecker {
            /**
             * @param list<array{name: string, status: string, detail: string}> $results
             */
            public function __construct(private readonly array $results)
            {
                parent::__construct();
            }

            public function check(): array
            {
                return $this->results;
            }
        };
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function row(string $name, string $status, string $detail): array
    {
        return ['name' => $name, 'status' => $status, 'detail' => $detail];
    }
}
