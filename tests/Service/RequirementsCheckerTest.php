<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests\Service;

use PHPUnit\Framework\TestCase;
use SahilPHP\Installer\Service\RequirementsChecker;

final class RequirementsCheckerTest extends TestCase
{
    public function testCheckReportsWellFormedResults(): void
    {
        $results = (new RequirementsChecker())->check();

        self::assertNotEmpty($results);

        $names = [];

        foreach ($results as $result) {
            self::assertArrayHasKey('name', $result);
            self::assertArrayHasKey('status', $result);
            self::assertArrayHasKey('detail', $result);
            self::assertContains($result['status'], [
                RequirementsChecker::OK,
                RequirementsChecker::WARNING,
                RequirementsChecker::ERROR,
            ]);
            self::assertNotSame('', $result['detail']);

            $names[] = $result['name'];
        }

        self::assertContains('php', $names);
        self::assertContains('composer', $names);
        self::assertContains('working-directory', $names);
    }

    public function testPhpVersionCheckPassesOnASupportedRuntime(): void
    {
        $results = (new RequirementsChecker())->check();

        $php = array_values(array_filter(
            $results,
            static fn (array $result): bool => 'php' === $result['name']
        ));

        self::assertCount(1, $php);
        self::assertSame(RequirementsChecker::OK, $php[0]['status']);
    }

    public function testUnsupportedPhpVersionIsReportedAsAnError(): void
    {
        $results = (new RequirementsChecker(null, '99.0.0'))->check();

        $php = array_values(array_filter(
            $results,
            static fn (array $result): bool => 'php' === $result['name']
        ));

        self::assertCount(1, $php);
        self::assertSame(RequirementsChecker::ERROR, $php[0]['status']);
        self::assertStringContainsString('99.0.0', $php[0]['detail']);
    }

    public function testCustomExtensionListIsChecked(): void
    {
        $results = (new RequirementsChecker(['definitely_not_a_php_extension']))->check();

        $extensionResults = array_values(array_filter(
            $results,
            static fn (array $result): bool => 'ext-definitely_not_a_php_extension' === $result['name']
        ));

        self::assertCount(1, $extensionResults);
        self::assertSame(RequirementsChecker::ERROR, $extensionResults[0]['status']);
    }
}
