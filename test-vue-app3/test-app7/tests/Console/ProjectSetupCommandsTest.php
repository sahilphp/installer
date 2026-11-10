<?php

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\TestCase;

/**
 * End-to-end checks of the starter-kit's project setup commands.
 *
 * `key:generate` and `storage:prepare` run before the application boots, so
 * they are exercised the same way `composer create-project` runs them: through
 * `sahil` in a directory that only has `.env.example`.
 */
final class ProjectSetupCommandsTest extends TestCase
{
    private string $project;

    protected function setUp(): void
    {
        $this->project = sys_get_temp_dir() . '/sahil-setup-' . bin2hex(random_bytes(6));

        if (!mkdir($this->project, 0o777, true) && !is_dir($this->project)) {
            self::fail('Unable to create a temporary project directory.');
        }

        file_put_contents($this->project . '/.env.example', "# example\nAPP_KEY=\nAPP_ENV=development\n");
    }

    protected function tearDown(): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->project, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($this->project);
    }

    /**
     * @return array{int, string, string}
     */
    private function runCommand(string $command): array
    {
        $root = dirname(__DIR__, 2);
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, $root . '/sahil', $command, '--path=' . $this->project],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
        );

        self::assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }

    public function test_key_generate_creates_the_env_file_and_writes_a_valid_key(): void
    {
        [$code, $stdout] = $this->runCommand('key:generate');

        self::assertSame(0, $code, $stdout);
        self::assertStringContainsString('Created .env from .env.example.', $stdout);

        $env = (string) file_get_contents($this->project . '/.env');
        self::assertStringContainsString('APP_ENV=development', $env, 'unrelated values must survive');
        self::assertMatchesRegularExpression('/^APP_KEY=base64:[A-Za-z0-9+\/=]{44}$/m', $env);
    }

    public function test_key_generate_refuses_to_replace_an_existing_key_without_force(): void
    {
        $this->runCommand('key:generate');
        $before = (string) file_get_contents($this->project . '/.env');

        [$code, , $stderr] = $this->runCommand('key:generate');

        self::assertSame(1, $code);
        self::assertStringContainsString('APP_KEY is already set; pass --force', $stderr);
        self::assertSame($before, (string) file_get_contents($this->project . '/.env'));
    }

    public function test_key_generate_force_replaces_the_key(): void
    {
        $this->runCommand('key:generate');
        preg_match('/^APP_KEY=(.+)$/m', (string) file_get_contents($this->project . '/.env'), $before);

        $pipes = [];
        $root = dirname(__DIR__, 2);
        $process = proc_open(
            [PHP_BINARY, $root . '/sahil', 'key:generate', '--force', '--path=' . $this->project],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
        );
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), $stdout . $stderr);

        preg_match('/^APP_KEY=(.+)$/m', (string) file_get_contents($this->project . '/.env'), $after);

        self::assertNotSame($before[1] ?? null, $after[1] ?? null);
        self::assertMatchesRegularExpression('/^base64:[A-Za-z0-9+\/=]{44}$/', $after[1] ?? '');
    }

    public function test_storage_prepare_creates_the_writable_directories(): void
    {
        [$code, $stdout] = $this->runCommand('storage:prepare');

        self::assertSame(0, $code, $stdout);

        foreach (['framework/views', 'framework/sessions', 'framework/cache', 'logs', 'uploads'] as $directory) {
            self::assertDirectoryExists($this->project . '/storage/' . $directory);
        }

        [$again, $stdoutAgain] = $this->runCommand('storage:prepare');

        self::assertSame(0, $again);
        self::assertStringContainsString('storage: ready.', $stdoutAgain);
    }
}
