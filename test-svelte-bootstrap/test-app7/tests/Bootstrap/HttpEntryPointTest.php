<?php

declare(strict_types=1);

namespace Tests\Bootstrap;

use PHPUnit\Framework\TestCase;

/**
 * Serves the starter-kit's real public front controller with PHP's built-in
 * server and checks the HTTP contract an operator sees.
 *
 * Each test gets its own free port and log directory, so runs do not collide
 * with a local development server or with each other.
 */
final class HttpEntryPointTest extends TestCase
{
    /**
     * @var resource|null
     */
    private $serverProcess = null;

    private string $port = '';

    private string $root = '';

    private string $logDirectory = '';

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
        $this->port = (string) $this->freePort();
        $this->logDirectory = sys_get_temp_dir().'/sahil-http-'.bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->logDirectory, 0o700, true));
    }

    public function test_health_endpoint_serves_expected_json_response(): void
    {
        [$status, $body, $contentType] = $this->request('/health');

        self::assertSame(200, $status);
        self::assertSame('application/json', $contentType);

        $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('ok', $payload['status']);
        self::assertSame('SahilPHP', $payload['framework']);
        self::assertTrue($payload['booted']);
        self::assertSame(PHP_VERSION, $payload['php']);
    }

    public function test_home_page_uses_sahilphp_identity_and_session_cookie(): void
    {
        [$status, $body, $contentType, $headers] = $this->request('/');

        self::assertSame(200, $status);
        self::assertSame('text/html; charset=utf-8', $contentType);
        self::assertStringContainsString('<meta name="generator" content="SahilPHP">', $body);
        self::assertMatchesRegularExpression('/<title>\s*Welcome · SahilPHP\s*<\/title>/u', $body);
        self::assertStringNotContainsString('laravel', strtolower($body));

        $responseHeaders = implode("\n", $headers);

        self::assertStringContainsString('Set-Cookie: sahil_session=', $responseHeaders);
        self::assertStringNotContainsString('laravel_session', $responseHeaders);
        self::assertStringNotContainsString('XSRF-TOKEN', $responseHeaders);
    }

    public function test_unknown_paths_respond_with_safe_404(): void
    {
        [$status, $body] = $this->request('/no-such-page');

        self::assertSame(404, $status);
        self::assertSame(
            ['error' => 'http_error', 'status' => 404],
            json_decode($body, true, 512, JSON_THROW_ON_ERROR),
            'kernel-rendered 404: status shown, no application internals'
        );
        self::assertStringNotContainsString('laravel', strtolower($body));
    }

    public function test_no_stack_trace_or_installation_path_leaks_into_error_responses(): void
    {
        [, $body] = $this->request('/no-such-page');

        self::assertStringNotContainsString('Fatal error', $body);
        self::assertStringNotContainsString('/home/', $body);
        self::assertStringNotContainsString($this->root, $body);
    }

    protected function tearDown(): void
    {
        $this->stopBuiltInServer();
        $this->removeDirectory($this->logDirectory);

        parent::tearDown();
    }

    /**
     * Start the built-in server running the real front controller on this
     * test's port, wait for readiness, fetch the given path once, and answer
     * with [status code, body, content-type, headers].
     *
     * @return array{0: int, 1: string, 2: string, 3: list<string>}
     */
    private function request(string $path): array
    {
        $this->startBuiltInServer();

        $url = 'http://127.0.0.1:'.$this->port.$path;
        $deadline = microtime(true) + 10.0;
        $lastError = 'no response';

        do {
            error_clear_last();
            $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 2]]);
            $response = @file_get_contents($url, false, $context);
            $headers = $http_response_header ?? [];

            if ($response !== false) {
                return $this->parseResponse($path, $response, $headers);
            }

            $lastError = error_get_last()['message'] ?? 'no response';

            if (!$this->serverIsRunning()) {
                self::fail($this->diagnostics($path, $lastError, 'the server process exited before answering'));
            }

            usleep(50_000);
        } while (microtime(true) < $deadline);

        self::fail($this->diagnostics($path, $lastError, 'the server did not answer within 10 seconds'));
    }

    /**
     * @param list<string> $headers
     *
     * @return array{0: int, 1: string, 2: string, 3: list<string>}
     */
    private function parseResponse(string $path, string $body, array $headers): array
    {
        $status = 0;
        $statusLine = '';
        $contentType = '';

        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                $statusLine = $header;
                $status = (int) $matches[1];
            } elseif (str_starts_with(strtolower($header), 'content-type:')) {
                $contentType = trim(strtolower(substr($header, 13)));
            }
        }

        if ($statusLine === '') {
            self::fail($this->diagnostics($path, 'no HTTP status line', 'the response had no status line'));
        }

        return [$status, $body, $contentType, $headers];
    }

    private function startBuiltInServer(): void
    {
        if (is_resource($this->serverProcess)) {
            return;
        }

        $entryPoint = $this->root.'/public/index.php';

        self::assertFileExists($entryPoint, 'The starter-kit public front controller must exist.');

        $command = [PHP_BINARY, '-S', '127.0.0.1:'.$this->port, $entryPoint];
        $descriptors = [
            1 => ['file', $this->logDirectory.'/server.log', 'a'],
            2 => ['file', $this->logDirectory.'/server.err.log', 'a'],
        ];

        $pipes = [];
        $handle = proc_open($command, $descriptors, $pipes, $this->root);

        self::assertIsResource($handle, 'Unable to start the PHP built-in server.');

        $this->serverProcess = $handle;
    }

    private function serverIsRunning(): bool
    {
        if (!is_resource($this->serverProcess)) {
            return false;
        }

        $status = proc_get_status($this->serverProcess);

        return $status['running'];
    }

    private function stopBuiltInServer(): void
    {
        if (!is_resource($this->serverProcess)) {
            $this->serverProcess = null;

            return;
        }

        proc_terminate($this->serverProcess);

        $deadline = microtime(true) + 2.0;

        while ($this->serverIsRunning() && microtime(true) < $deadline) {
            usleep(20_000);
        }

        proc_close($this->serverProcess);
        $this->serverProcess = null;
    }

    private function diagnostics(string $path, string $reason, string $summary): string
    {
        $status = is_resource($this->serverProcess) ? proc_get_status($this->serverProcess) : null;

        return sprintf(
            "%s\nURL: http://127.0.0.1:%s%s\nReason: %s\nServer running: %s\nSTDERR:\n%s",
            $summary,
            $this->port,
            $path,
            $reason,
            $status === null ? 'no' : ($status['running'] ? 'yes' : 'no'),
            $this->logTail('server.err.log'),
        );
    }

    private function logTail(string $name): string
    {
        $path = $this->logDirectory.'/'.$name;

        if (!is_file($path)) {
            return '(no server log)';
        }

        $contents = (string) file_get_contents($path);

        return $contents === '' ? '(empty server log)' : substr($contents, -2000);
    }

    private function freePort(): int
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);

        self::assertIsResource($socket, sprintf(
            'Unable to reserve a local port: %s (%d).',
            $errorMessage,
            $errorCode,
        ));

        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        self::assertIsString($name, 'Unable to read the reserved port.');

        return (int) substr($name, (int) strrpos($name, ':') + 1);
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
