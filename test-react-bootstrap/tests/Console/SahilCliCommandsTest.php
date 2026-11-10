<?php

declare(strict_types=1);

namespace Tests\Console;

use Sahil\Testing\ApplicationTestCase;

/**
 * End-to-end checks of the real `sahil` entry point.
 *
 * Each command runs in its own PHP process, so these tests also prove the
 * binary boots from a clean process rather than from this test's memory.
 */
final class SahilCliCommandsTest extends ApplicationTestCase
{
    /**
     * The starter-kit installation is the application under test; its
     * `sahil` is the console binary ApplicationTestCase runs.
     */
    protected function basePath(): string
    {
        return dirname(__DIR__, 2);
    }

    public function test_about_reports_application_facts(): void
    {
        $this->runCommand('about')
            ->assertSuccessful()
            ->assertOutputContains('app name:')
            ->assertOutputContains('SahilPHP')
            ->assertOutputContains('php:')
            ->assertOutputContains('booted:');
    }

    public function test_config_show_redacts_secret_values(): void
    {
        $this->runCommand(['config:show', 'database'])
            ->assertSuccessful()
            ->assertOutputContains('database.password')
            ->assertOutputContains('[redacted]')
            ->assertOutputMissing('DB_PASSWORD=');
    }

    public function test_env_check_lists_documented_variables_without_values(): void
    {
        $this->runCommand('env:check')
            ->assertSuccessful()
            ->assertOutputContains('APP_ENV')
            ->assertOutputMissing('APP_KEY=');
    }

    public function test_unknown_option_prints_help_to_stderr_and_exits_one(): void
    {
        $this->runCommand('--made-up')
            ->assertFailed()
            ->assertExitCode(1)
            ->assertOutputContains('Unknown option')
            ->assertOutputContains('php sahil <command>');
    }

    public function test_version_prints_framework_and_php_versions(): void
    {
        $this->runCommand('--version')
            ->assertSuccessful()
            ->assertOutputContains('SahilPHP')
            ->assertOutputContains(PHP_VERSION);
    }

    public function test_help_documents_unda_generators_and_never_the_internal_binary_path(): void
    {
        $this->runCommand([])
            ->assertSuccessful()
            ->assertOutputContains('Code Generation')
            ->assertOutputContains('unda:controller')
            ->assertOutputContains('unda:model')
            ->assertOutputContains('unda:migration')
            ->assertOutputContains('unda:middleware')
            ->assertOutputContains('unda:policy')
            ->assertOutputContains('unda:job')
            ->assertOutputContains('php sahil <command>')
            ->assertOutputMissing('bin/sahil')
            ->assertOutputMissing('make:controller');
    }

    public function test_generators_write_to_an_explicit_application_root(): void
    {
        $root = $this->tempDirectory('sahil-generator-app');
        mkdir($root.'/app', 0700, true);

        file_put_contents($root.'/composer.json', json_encode([
            'name' => 'acme/application',
            'autoload' => ['psr-4' => ['Acme\\' => 'app/']],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->runCommand(['unda:controller', 'Admin/UserController', '--path='.$root])
            ->assertSuccessful()
            ->assertOutputContains('app/Http/Controllers/Admin/UserController.php')
            ->assertOutputContains('namespace Acme\\Http\\Controllers\\Admin');

        $file = $root.'/app/Http/Controllers/Admin/UserController.php';
        self::assertFileExists($file);
        self::assertStringContainsString(
            'namespace Acme\\Http\\Controllers\\Admin;',
            (string) file_get_contents($file),
        );

        $lint = [];
        $status = 0;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $lint, $status);
        self::assertSame(0, $status, implode("\n", $lint));
    }
}
