<?php

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\TestCase;

/**
 * `unda:auth` (and its `make:auth` compatibility alias) writes the complete auth implementation in one command.
 *
 * Every test runs the real CLI against a temporary project directory, so the
 * assertions cover the files a user receives, not an in-memory plan.
 */
final class MakeAuthCommandTest extends TestCase
{
    private string $project;

    protected function setUp(): void
    {
        $this->project = sys_get_temp_dir() . '/sahil-auth-' . bin2hex(random_bytes(6));

        if (!mkdir($this->project, 0o777, true) && !is_dir($this->project)) {
            self::fail('Unable to create a temporary project directory.');
        }
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
     * @return array{0: int, 1: string, 2: string} exit code, stdout, stderr
     */
    private function runCommand(string ...$options): array
    {
        $root = dirname(__DIR__, 2);
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, $root . '/sahil', 'make:auth', '--path=' . $this->project, ...$options],
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

    /**
     * @return list<string>
     */
    private function generatedFiles(): array
    {
        return [
            'app/Models/User.php',
            'app/Http/Controllers/Auth/LoginController.php',
            'app/Http/Controllers/Auth/RegisterController.php',
            'app/Http/Controllers/Auth/ForgotPasswordController.php',
            'app/Http/Controllers/Auth/ResetPasswordController.php',
            'app/Http/Middleware/Authenticate.php',
            'app/Http/Middleware/RedirectIfAuthenticated.php',
            'database/migrations/2026_10_30_000000_create_users_table.php',
            'database/migrations/2026_10_30_000001_create_password_reset_tokens_table.php',
            'database/migrations/2026_10_30_000002_create_sessions_table.php',
            'routes/auth.php',
            'resources/views/layouts/auth.sahil.php',
            'resources/views/layouts/dashboard.sahil.php',
            'resources/views/auth/login.sahil.php',
            'resources/views/auth/register.sahil.php',
            'resources/views/auth/forgot-password.sahil.php',
            'resources/views/auth/reset-password.sahil.php',
            'resources/views/pages/dashboard.sahil.php',
        ];
    }

    public function test_scaffolds_every_auth_file_in_one_command(): void
    {
        [$code, $stdout, $stderr] = $this->runCommand('--ui=tailwind');

        self::assertSame(0, $code, $stdout . $stderr);
        self::assertStringContainsString('Auth scaffolding complete.', $stdout);
        self::assertStringContainsString('1. Run: php sahil migrate', $stdout);
        self::assertStringContainsString('2. Visit /register to create your first user.', $stdout);

        foreach ($this->generatedFiles() as $relative) {
            self::assertFileExists($this->project . '/' . $relative, $relative . ' was not generated');
        }
    }

    public function test_every_generated_php_file_parses(): void
    {
        $this->runCommand('--ui=tailwind');

        foreach ($this->generatedFiles() as $relative) {
            if (!str_ends_with($relative, '.php')) {
                continue;
            }

            $pipes = [];
            $process = proc_open(
                [PHP_BINARY, '-l', $this->project . '/' . $relative],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );

            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]) ?: '';
            $errors = stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);

            self::assertSame(0, proc_close($process), $relative . ': ' . $output . $errors);
        }
    }

    public function test_generated_content_matches_the_auth_contract(): void
    {
        $this->runCommand('--ui=tailwind');

        $routes = (string) file_get_contents($this->project . '/routes/auth.php');
        self::assertStringContainsString("Route::get('/login'", $routes);
        self::assertStringContainsString("Route::post('/login'", $routes);
        self::assertStringContainsString("Route::get('/register'", $routes);
        self::assertStringContainsString("Route::post('/register'", $routes);
        self::assertStringContainsString("Route::get('/forgot-password'", $routes);
        self::assertStringContainsString("Route::post('/forgot-password'", $routes);
        self::assertStringContainsString("Route::get('/reset-password/{token}'", $routes);
        self::assertStringContainsString("Route::post('/reset-password'", $routes);
        self::assertStringContainsString("Route::post('/logout'", $routes);
        self::assertStringContainsString("->name('login')", $routes);
        self::assertStringContainsString("->name('register')", $routes);
        self::assertStringContainsString('RedirectIfAuthenticated::class', $routes);
        self::assertStringContainsString('Authenticate::class', $routes);

        $login = (string) file_get_contents($this->project . '/app/Http/Controllers/Auth/LoginController.php');
        self::assertStringContainsString('AuthManager', $login);
        self::assertStringContainsString('->attempt(', $login);
        self::assertStringContainsString('->logout()', $login);
        self::assertStringContainsString("Response::redirect('/dashboard', 303)", $login);

        $register = (string) file_get_contents($this->project . '/app/Http/Controllers/Auth/RegisterController.php');
        self::assertStringContainsString('UserRepository', $register);
        self::assertStringContainsString('password_confirmation', $register);
        self::assertStringContainsString('length:8,255', $register);

        $forgot = (string) file_get_contents($this->project . '/app/Http/Controllers/Auth/ForgotPasswordController.php');
        self::assertStringContainsString('PasswordResetManager', $forgot);
        self::assertStringContainsString('MailerContract', $forgot);

        $reset = (string) file_get_contents($this->project . '/app/Http/Controllers/Auth/ResetPasswordController.php');
        self::assertStringContainsString('validateToken(', $reset);
        self::assertStringContainsString('updatePassword(', $reset);
        self::assertStringContainsString('->login(', $reset);

        $userModel = (string) file_get_contents($this->project . '/app/Models/User.php');
        self::assertStringContainsString('AuthenticatableContract', $userModel);
        self::assertStringContainsString("'password' => 'hashed'", $userModel);
        self::assertStringContainsString("'email_verified_at' => 'datetime'", $userModel);
        self::assertStringContainsString('getAuthIdentifier()', $userModel);
        self::assertStringContainsString('getAuthPassword()', $userModel);

        $usersMigration = (string) file_get_contents(
            $this->project . '/database/migrations/2026_10_30_000000_create_users_table.php',
        );
        self::assertStringContainsString("\$table->string('name', 255)", $usersMigration);
        self::assertStringContainsString("\$table->string('email', 255)->unique()", $usersMigration);
        self::assertStringContainsString("\$table->timestamp('email_verified_at')->nullable()", $usersMigration);
        self::assertStringContainsString("\$table->string('remember_token', 100)->nullable()", $usersMigration);

        $resetMigration = (string) file_get_contents(
            $this->project . '/database/migrations/2026_10_30_000001_create_password_reset_tokens_table.php',
        );
        self::assertStringContainsString("\$table->string('email', 255)->primary()", $resetMigration);
        self::assertStringContainsString("\$table->string('token', 255)", $resetMigration);

        $sessions = (string) file_get_contents(
            $this->project . '/database/migrations/2026_10_30_000002_create_sessions_table.php',
        );
        self::assertStringContainsString("\$table->string('id', 255)->primary()", $sessions);
        self::assertStringContainsString("\$table->longText('payload')", $sessions);
        self::assertStringContainsString("\$table->integer('last_activity')", $sessions);

        $loginView = (string) file_get_contents($this->project . '/resources/views/auth/login.sahil.php');
        self::assertStringContainsString('name="_token"', $loginView);
        self::assertStringContainsString('bg-primary', $loginView);

        $resetView = (string) file_get_contents($this->project . '/resources/views/auth/reset-password.sahil.php');
        self::assertStringContainsString('action="/reset-password"', $resetView);
        self::assertStringContainsString('name="token"', $resetView);
    }

    public function test_adds_the_auth_require_line_to_web_routes_once(): void
    {
        $routes = "<?php\n\ndeclare(strict_types=1);\n\nreturn [];\n";
        mkdir($this->project . '/routes', 0o777, true);
        file_put_contents($this->project . '/routes/web.php', $routes);

        $this->runCommand('--ui=tailwind');
        $this->runCommand('--ui=tailwind');

        $contents = (string) file_get_contents($this->project . '/routes/web.php');
        self::assertSame(1, substr_count($contents, "require __DIR__.'/auth.php';"));
    }

    public function test_existing_files_are_kept_without_force(): void
    {
        $this->runCommand('--ui=tailwind');

        $loginPath = $this->project . '/resources/views/auth/login.sahil.php';
        file_put_contents($loginPath, "{{-- edited by the application --}}\n");

        [$code, $stdout] = $this->runCommand('--ui=tailwind');

        self::assertSame(0, $code);
        self::assertStringContainsString('skipped', $stdout);
        self::assertSame("{{-- edited by the application --}}\n", (string) file_get_contents($loginPath));
    }

    public function test_force_overwrites_existing_files_and_keeps_a_backup(): void
    {
        $this->runCommand('--ui=tailwind');

        $loginPath = $this->project . '/resources/views/auth/login.sahil.php';
        file_put_contents($loginPath, "{{-- edited by the application --}}\n");

        [$code, $stdout] = $this->runCommand('--ui=tailwind', '--force');

        self::assertSame(0, $code);
        self::assertStringContainsString('updated', $stdout);
        self::assertStringContainsString('backup', $stdout);
        self::assertStringNotContainsString('edited by the application', (string) file_get_contents($loginPath));

        $backups = glob($this->project . '/storage/framework/auth-backups/*/resources/views/auth/login.sahil.php') ?: [];
        self::assertCount(1, $backups);
        self::assertStringContainsString('edited by the application', (string) file_get_contents($backups[0]));
    }

    public function test_existing_users_migration_is_skipped_with_a_warning(): void
    {
        mkdir($this->project . '/database/migrations', 0o777, true);
        file_put_contents(
            $this->project . '/database/migrations/2025_01_01_000000_create_users_table.php',
            "<?php\n",
        );

        [$code, $stdout] = $this->runCommand('--ui=tailwind');

        self::assertSame(0, $code);
        self::assertStringContainsString('already exists in database/migrations', $stdout);
        self::assertFileDoesNotExist($this->project . '/database/migrations/2026_10_30_000000_create_users_table.php');
    }

    public function test_profile_is_detected_from_the_ui_manifest(): void
    {
        file_put_contents($this->project . '/.sahil-ui.json', "{\"profile\":\"bootstrap\"}\n");

        [$code, $stdout, $stderr] = $this->runCommand();

        self::assertSame(0, $code, $stdout . $stderr);

        $login = (string) file_get_contents($this->project . '/resources/views/auth/login.sahil.php');
        self::assertStringContainsString('btn btn-primary', $login);
    }

    public function test_missing_profile_defaults_to_tailwind_instead_of_failing(): void
    {
        [$code, $stdout, $stderr] = $this->runCommand();

        self::assertSame(0, $code, $stdout . $stderr);
        self::assertFileExists($this->project . '/routes/auth.php');
        self::assertStringContainsString('bg-primary', (string) file_get_contents(
            $this->project . '/resources/views/auth/login.sahil.php',
        ));
    }

    public function test_unknown_profile_is_rejected(): void
    {
        [$code, , $stderr] = $this->runCommand('--ui=bulma');

        self::assertSame(1, $code);
        self::assertStringContainsString('unknown UI profile [bulma]', $stderr);
    }

    public function test_dry_run_reports_the_plan_without_writing(): void
    {
        [$code, $stdout] = $this->runCommand('--ui=tailwind', '--dry-run');

        self::assertSame(0, $code);
        self::assertStringContainsString('Would scaffold', $stdout);
        self::assertFileDoesNotExist($this->project . '/routes/auth.php');
        self::assertFileDoesNotExist($this->project . '/app/Models/User.php');
    }
}
