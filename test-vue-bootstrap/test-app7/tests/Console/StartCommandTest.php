<?php

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\TestCase;

/**
 * `start` validates its bind address and, when the port is taken, moves to the
 * next free port and starts there instead of failing with Composer's raw
 * script error.
 */
final class StartCommandTest extends TestCase
{
    /**
     * @return array{0: int, 1: string, 2: string}
     */
    private function runStart(string ...$arguments): array
    {
        $root = dirname(__DIR__, 2);
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, $root . '/sahil', 'start', ...$arguments],
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
     * Start the server without blocking, and collect output until it has
     * printed its banner.
     *
     * @param list<string> $arguments
     * @return array{0: resource, 1: array<int, resource>, 2: string, 3: string}
     */
    private function startInBackground(array $arguments): array
    {
        $root = dirname(__DIR__, 2);
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, $root . '/sahil', 'start', ...$arguments],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
        );

        self::assertIsResource($process);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + 8.0;

        while (microtime(true) < $deadline) {
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);

            if (str_contains($stdout, 'press Ctrl-C to stop')) {
                break;
            }

            usleep(50_000);
        }

        return [$process, $pipes, $stdout, $stderr];
    }

    /**
     * @param resource $process
     * @param array<int, resource> $pipes
     */
    private function stopServer($process, array $pipes): void
    {
        proc_terminate($process);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        proc_close($process);
    }

    /**
     * The banner is printed before PHP's server starts accepting, so poll the
     * port instead of connecting once.
     */
    private function waitForPort(int $port, float $timeout = 5.0): bool
    {
        $deadline = microtime(true) + $timeout;

        do {
            $connection = @stream_socket_client('tcp://127.0.0.1:' . $port, $error, $message, 0.5);

            if (is_resource($connection)) {
                fclose($connection);

                return true;
            }

            usleep(100_000);
        } while (microtime(true) < $deadline);

        return false;
    }

    /**
     * @return array{0: resource, 1: int}
     */
    private function occupyPort(): array
    {
        $listener = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);

        self::assertIsResource($listener, (string) $errorMessage);

        $name = (string) stream_socket_get_name($listener, false);
        $port = (int) substr($name, strrpos($name, ':') + 1);

        self::assertGreaterThan(0, $port);

        return [$listener, $port];
    }

    public function test_busy_port_falls_back_to_the_next_free_port(): void
    {
        [$listener, $busy] = $this->occupyPort();

        [$process, $pipes, $stdout, $stderr] = $this->startInBackground(['--port=' . $busy]);

        try {
            self::assertStringContainsString(
                sprintf('127.0.0.1:%d is already in use', $busy),
                $stdout,
                $stdout . $stderr
            );

            preg_match('/using (\d+) instead/', $stdout, $matches);
            $chosen = (int) ($matches[1] ?? 0);

            self::assertGreaterThan($busy, $chosen, 'the fallback port must be above the busy one: ' . $stdout);
            self::assertStringContainsString(sprintf('http://127.0.0.1:%d', $chosen), $stdout);

            self::assertTrue(
                $this->waitForPort($chosen),
                sprintf('the fallback port %d must serve requests', $chosen)
            );
        } finally {
            fclose($listener);
            $this->stopServer($process, $pipes);
        }
    }

    public function test_space_separated_host_and_port_are_accepted(): void
    {
        [$listener, $busy] = $this->occupyPort();

        [$process, $pipes, $stdout, $stderr] = $this->startInBackground([
            '--host',
            '127.0.0.1',
            '--port',
            (string) $busy,
        ]);

        try {
            self::assertStringContainsString(
                sprintf('127.0.0.1:%d is already in use', $busy),
                $stdout,
                $stdout . $stderr
            );
            self::assertMatchesRegularExpression('/using \d+ instead/', $stdout);
        } finally {
            fclose($listener);
            $this->stopServer($process, $pipes);
        }
    }

    public function test_invalid_ports_are_rejected_before_binding(): void
    {
        foreach (['0', '65536', 'eighty', '-1', '80.5'] as $port) {
            [$code, , $stderr] = $this->runStart('--port=' . $port);

            self::assertSame(1, $code, sprintf('port [%s] must be rejected', $port));
            self::assertStringContainsString('invalid port', $stderr, $port);
            self::assertStringContainsString('between 1 and 65535', $stderr, $port);
        }
    }

    public function test_invalid_host_is_rejected(): void
    {
        [$code, , $stderr] = $this->runStart('--host=not a host');

        self::assertSame(1, $code);
        self::assertStringContainsString('invalid host', $stderr);
    }

    public function test_unknown_option_is_rejected(): void
    {
        [$code, , $stderr] = $this->runStart('--turbo');

        self::assertSame(1, $code);
        self::assertStringContainsString('unknown option [--turbo]', $stderr);
    }

    public function test_start_help_prints_the_command_reference(): void
    {
        [$code, $stdout] = $this->runStart('--help');

        self::assertSame(0, $code);
        self::assertStringContainsString('start', $stdout);
        self::assertStringContainsString('--port=PORT', $stdout);
        self::assertStringContainsString('--host=HOST', $stdout);
    }

    public function test_root_entry_point_runs_the_same_cli(): void
    {
        $root = dirname(__DIR__, 2);
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, $root . '/sahil', '--version'],
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
        self::assertStringContainsString('SahilPHP', $stdout);
        self::assertStringContainsString(PHP_VERSION, $stdout);
    }
}
