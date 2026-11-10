<?php

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\TestCase;

/**
 * End-to-end checks of the starter-kit's `ui:status` command.
 *
 * `ui:status` is the read-only frontend readiness report and runs before the
 * application boots: it must name the detected profile and its source, list
 * missing generated sources, npm packages and build outputs, keep its exit
 * code in step with readiness, and offer a machine-readable `--json` report.
 */
final class UiStatusCommandTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            $this->removeDirectory($directory);
        }

        $this->temporaryDirectories = [];

        parent::tearDown();
    }

    public function test_status_reports_the_project_as_not_ready_until_assets_are_built(): void
    {
        $project = $this->project('tailwind', [
            'package.json' => '{"name":"status-fixture"}',
            'vite.config.js' => "export default {};\n",
            'resources/css/tailwind.css' => "@import 'tailwindcss';\n",
        ]);

        [$code, $stdout] = $this->runStatus(['--path='.$project]);

        self::assertSame(1, $code, 'A project with missing build output must not report readiness.');
        self::assertStringContainsString('UI status: '.realpath($project), $stdout);
        self::assertStringContainsString(
            'profile:          Tailwind CSS v4 (tailwind, from .sahil-ui.json)',
            $stdout
        );
        self::assertStringContainsString('generated files:  3/3 present', $stdout);
        self::assertStringContainsString('missing public/build/assets/tailwind.css', $stdout);
        self::assertStringContainsString(
            'Missing build output(s): public/build/assets/tailwind.css',
            $stdout
        );
        self::assertStringContainsString('ready:            no', $stdout);
    }

    public function test_status_json_exposes_the_readiness_contract(): void
    {
        $project = $this->project('tailwind', [
            'package.json' => '{"name":"status-fixture"}',
            'vite.config.js' => "export default {};\n",
            'resources/css/tailwind.css' => "@import 'tailwindcss';\n",
        ]);

        [$code, $stdout] = $this->runStatus(['--path='.$project, '--json']);

        self::assertSame(1, $code);

        /** @var array<string, mixed> $report */
        $report = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($report['ok']);
        self::assertSame(realpath($project), $report['base_path']);
        self::assertSame('tailwind', $report['profile']);
        self::assertSame('.sahil-ui.json', $report['profile_source']);
        self::assertNull($report['assets']['public/build/assets/tailwind.css']);
        self::assertContains('public/build/assets/tailwind.css', $report['missing_assets']);
        self::assertNotSame([], $report['issues']);
    }

    public function test_status_is_ready_when_sources_packages_and_build_outputs_are_present(): void
    {
        $project = $this->project('none', [
            'package.json' => '{"name":"status-fixture"}',
            'vite.config.js' => "export default {};\n",
            'resources/css/app.css' => "body { margin: 0; }\n",
            'node_modules/vite/package.json' => '{"name":"vite"}',
            'public/build/assets/app.css' => "body{margin:0}\n",
        ]);

        [$code, $stdout] = $this->runStatus(['--path='.$project], ['PATH' => $this->fakeToolchain()]);

        self::assertSame(0, $code, 'A fully installed project must report readiness.');
        self::assertStringContainsString('profile:          Plain CSS (no framework) (none, from .sahil-ui.json)', $stdout);
        self::assertStringContainsString('generated files:  3/3 present', $stdout);
        self::assertStringContainsString('npm packages:     1/1 installed', $stdout);
        self::assertStringContainsString('ok      public/build/assets/app.css', $stdout);
        self::assertStringContainsString('ready:            yes', $stdout);
    }

    /**
     * @param array<string, string> $files
     */
    private function project(string $profile, array $files): string
    {
        $directory = $this->tempDirectory('sahil-ui-status');

        $files = ['.sahil-ui.json' => json_encode(['profile' => $profile], JSON_THROW_ON_ERROR)] + $files;

        foreach ($files as $relative => $contents) {
            $path = $directory.'/'.$relative;

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0o777, true);
            }

            file_put_contents($path, $contents);
        }

        return $directory;
    }

    /**
     * A PATH containing fake node/npm executables, so readiness does not
     * depend on the machine running the tests.
     */
    private function fakeToolchain(): string
    {
        $directory = $this->tempDirectory('sahil-ui-toolchain');

        file_put_contents($directory.'/node', "#!/bin/sh\necho 'v24.0.0'\n");
        file_put_contents($directory.'/npm', "#!/bin/sh\necho '12.0.0'\n");

        chmod($directory.'/node', 0o755);
        chmod($directory.'/npm', 0o755);

        return $directory;
    }

    /**
     * @param list<string>          $arguments
     * @param array<string, string> $environment
     *
     * @return array{0: int, 1: string, 2: string} exit code, stdout, stderr
     */
    private function runStatus(array $arguments, array $environment = []): array
    {
        $root = dirname(__DIR__, 2);
        $command = [PHP_BINARY, $root.'/sahil', 'ui:status', ...$arguments];

        $pipes = [];
        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
            array_replace(getenv(), $environment),
        );

        self::assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }

    private function tempDirectory(string $prefix): string
    {
        $directory = sys_get_temp_dir().'/'.$prefix.'-'.bin2hex(random_bytes(6));

        self::assertTrue(mkdir($directory, 0o700, true));
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            if ($item->isDir() && !$item->isLink()) {
                @rmdir($item->getPathname());

                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($directory);
    }
}
