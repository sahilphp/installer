<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests\Command;

use PHPUnit\Framework\TestCase;
use SahilPHP\Installer\Command\NewCommand;
use Symfony\Component\Console\Tester\CommandTester;

final class NewCommandTest extends TestCase
{
    public function testNewCommandStillRuns(): void
    {
        $tester = new CommandTester(new NewCommand());

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Creating a new SahilPHP project...', $tester->getDisplay());
    }
}
