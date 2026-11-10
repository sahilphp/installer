<?php

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\TestCase;

/**
 * `ui:setup` installs exactly one UI kit and one CSS framework, writes a
 * package.json with only those dependencies, and deletes `stubs/`.
 */
final class UiSetupCommandTest extends TestCase
{
    private string $project;

    protected function setUp(): void
    {
        $this->project = sys_get_temp_dir() . '/sahil-ui-setup-' . bin2hex(random_bytes(6));

        if (!mkdir($this->project, 0o777, true) && !is_dir($this->project)) {
            self::fail('Unable to create a temporary project directory.');
        }

        // `ui:setup` operates on the project's own stubs tree.
        $this->copyTree(dirname(__DIR__, 2) . '/stubs', $this->project . '/stubs');
        if (!is_dir($this->project . '/bootstrap') && !mkdir($this->project . '/bootstrap', 0o777, true) && !is_dir($this->project . '/bootstrap')) {
            self::fail('Unable to create bootstrap directory.');
        }
        copy(dirname(__DIR__, 2) . '/bootstrap/commands.php', $this->project . '/bootstrap/commands.php');
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->project);
    }

    /**
     * @param array<string, string> $env
     *
     * @return array{0: int, 1: string, 2: string}
     */
    private function runCommand(array $env = [], string ...$options): array
    {
        $root = dirname(__DIR__, 2);
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, $root . '/sahil', 'ui:setup', '--path=' . $this->project, ...$options],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
            $env,
        );

        self::assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }

    /**
     * @return array<string, mixed>
     */
    private function package(): array
    {
        $decoded = json_decode((string) file_get_contents($this->project . '/package.json'), true);

        self::assertIsArray($decoded);

        return $decoded;
    }

    public function test_react_with_tailwind_installs_only_those_stubs(): void
    {
        [$code, $stdout, $stderr] = $this->runCommand([], '--ui-kit=react', '--css=tailwind');

        self::assertSame(0, $code, $stdout . $stderr);
        self::assertDirectoryDoesNotExist($this->project . '/stubs');
        self::assertFileExists($this->project . '/resources/js/App.jsx');
        self::assertFileExists($this->project . '/resources/js/app.js');
        self::assertFileExists($this->project . '/resources/css/tailwind.css');
        self::assertFileDoesNotExist($this->project . '/resources/css/app.css');
        self::assertFileExists($this->project . '/resources/views/layouts/app.sahil.php');
        self::assertStringContainsString("@asset_js('kit')", (string) file_get_contents($this->project . '/resources/views/layouts/app.sahil.php'));
        self::assertFileDoesNotExist($this->project . '/resources/js/App.vue');
        self::assertFileDoesNotExist($this->project . '/resources/js/App.svelte');

        $package = $this->package();
        self::assertArrayHasKey('react', $package['dependencies'] ?? []);
        self::assertArrayHasKey('@vitejs/plugin-react', $package['devDependencies'] ?? []);
        self::assertArrayHasKey('@tailwindcss/vite', $package['devDependencies'] ?? []);
        self::assertArrayNotHasKey('bootstrap', $package['devDependencies'] ?? []);
        self::assertArrayNotHasKey('vue', $package['dependencies'] ?? []);
        self::assertArrayNotHasKey('svelte', $package['dependencies'] ?? []);

        $vite = (string) file_get_contents($this->project . '/vite.config.js');
        self::assertStringContainsString('react()', $vite);
        self::assertStringContainsString('tailwindcss()', $vite);
        self::assertStringContainsString("kit: 'resources/js/app.js'", $vite);
        self::assertStringContainsString("tailwind: 'resources/css/tailwind.css'", $vite);

        $manifest = json_decode((string) file_get_contents($this->project . '/.sahil-ui.json'), true);
        self::assertSame(['profile' => 'tailwind', 'ui_kit' => 'react'], $manifest);
    }

    public function test_vue_with_bootstrap_installs_only_those_stubs(): void
    {
        [$code, $stdout, $stderr] = $this->runCommand([], '--ui-kit=vue', '--css=bootstrap');

        self::assertSame(0, $code, $stdout . $stderr);
        self::assertFileExists($this->project . '/resources/js/App.vue');
        self::assertFileDoesNotExist($this->project . '/resources/js/App.jsx');
        self::assertFileExists($this->project . '/resources/css/bootstrap.css');
        self::assertFileExists($this->project . '/resources/js/bootstrap.js');
        self::assertFileDoesNotExist($this->project . '/resources/css/tailwind.css');

        $package = $this->package();
        self::assertArrayHasKey('vue', $package['dependencies'] ?? []);
        self::assertArrayHasKey('@vitejs/plugin-vue', $package['devDependencies'] ?? []);
        self::assertArrayHasKey('bootstrap', $package['devDependencies'] ?? []);
        self::assertArrayNotHasKey('@tailwindcss/vite', $package['devDependencies'] ?? []);

        $vite = (string) file_get_contents($this->project . '/vite.config.js');
        self::assertStringContainsString('vue()', $vite);
        self::assertStringNotContainsString('tailwindcss', $vite);
    }

    public function test_svelte_with_tailwind_installs_only_those_stubs(): void
    {
        [$code, $stdout, $stderr] = $this->runCommand([], '--ui-kit=svelte', '--css=tailwind');

        self::assertSame(0, $code, $stdout . $stderr);
        self::assertFileExists($this->project . '/resources/js/App.svelte');
        self::assertArrayHasKey('svelte', $this->package()['dependencies'] ?? []);
    }

    public function test_firefiber_and_none_install_no_js_framework_packages(): void
    {
        [$code] = $this->runCommand([], '--ui-kit=firefiber', '--css=tailwind');
        self::assertSame(0, $code);

        $layout = (string) file_get_contents($this->project . '/resources/views/layouts/app.sahil.php');
        self::assertStringContainsString("@asset_css('@fiberstyles')", $layout);
        self::assertStringContainsString("@asset_js('@fiberscripts')", $layout);
        self::assertStringContainsString('window.Firefiber.boot()', (string) file_get_contents($this->project . '/resources/js/app.js'));
        $commands = (string) file_get_contents($this->project . '/bootstrap/commands.php');
        self::assertStringContainsString('FirefiberPageCommand::class', $commands);
        self::assertStringContainsString('FirefiberComponentCommand::class', $commands);

        $package = $this->package();
        self::assertArrayNotHasKey('react', $package['dependencies'] ?? []);
        self::assertArrayNotHasKey('vue', $package['dependencies'] ?? []);
        self::assertArrayNotHasKey('svelte', $package['dependencies'] ?? []);

        $this->removeTree($this->project);
        mkdir($this->project, 0o777, true);
        $this->copyTree(dirname(__DIR__, 2) . '/stubs', $this->project . '/stubs');
        mkdir($this->project . '/bootstrap', 0o777, true);
        copy(dirname(__DIR__, 2) . '/bootstrap/commands.php', $this->project . '/bootstrap/commands.php');

        [$code] = $this->runCommand([], '--ui-kit=none', '--css=tailwind');
        self::assertSame(0, $code);
        self::assertStringNotContainsString('FirefiberPageCommand::class', (string) file_get_contents($this->project . '/bootstrap/commands.php'));

        $package = $this->package();
        self::assertArrayNotHasKey('react', $package['dependencies'] ?? []);
        self::assertArrayNotHasKey('vue', $package['dependencies'] ?? []);
        self::assertArrayHasKey('@tailwindcss/vite', $package['devDependencies'] ?? []);
    }

    public function test_environment_variables_take_precedence_over_questions(): void
    {
        [$code, $stdout, $stderr] = $this->runCommand([
            'SAHILPHP_UI_KIT' => 'react',
            'SAHILPHP_CSS' => 'bootstrap',
        ]);

        self::assertSame(0, $code, $stdout . $stderr);
        self::assertFileExists($this->project . '/resources/js/App.jsx');
        self::assertArrayHasKey('bootstrap', $this->package()['devDependencies'] ?? []);
    }

    public function test_non_interactive_defaults_to_none_and_tailwind(): void
    {
        [$code, $stdout, $stderr] = $this->runCommand([], '--no-interaction');

        self::assertSame(0, $code, $stdout . $stderr);
        self::assertStringContainsString('using [none]', $stdout);
        self::assertStringContainsString('using [tailwind]', $stdout);
        self::assertFileExists($this->project . '/resources/css/tailwind.css');
        self::assertFileDoesNotExist($this->project . '/resources/css/app.css');
        self::assertArrayHasKey('@tailwindcss/vite', $this->package()['devDependencies'] ?? []);
    }

    public function test_invalid_ui_kit_fails_with_a_helpful_message(): void
    {
        [$code, , $stderr] = $this->runCommand([], '--ui-kit=angular', '--css=tailwind');

        self::assertSame(1, $code);
        self::assertStringContainsString('invalid ui-kit [angular]', $stderr);
        self::assertStringContainsString('firefiber, react, vue, svelte, none', $stderr);
        self::assertDirectoryExists($this->project . '/stubs');
    }

    public function test_invalid_css_fails_with_a_helpful_message(): void
    {
        [$code, , $stderr] = $this->runCommand([], '--ui-kit=none', '--css=bulma');

        self::assertSame(1, $code);
        self::assertStringContainsString('invalid css [bulma]', $stderr);
        self::assertStringContainsString('tailwind, bootstrap', $stderr);
    }

    private function copyTree(string $source, string $destination): void
    {
        if (!is_dir($destination) && !mkdir($destination, 0o777, true) && !is_dir($destination)) {
            self::fail('Unable to create ' . $destination);
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            $relative = substr($item->getPathname(), strlen($source) + 1);
            $target = $destination . '/' . $relative;

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0o777, true);
                }

                continue;
            }

            copy($item->getPathname(), $target);
        }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($path);
    }
}
