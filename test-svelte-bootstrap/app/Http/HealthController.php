<?php

declare(strict_types=1);

namespace App\Http;

use Sahil\Foundation\Application;

/**
 * Produces the /health payload.
 *
 * This class stays deliberately boring: it proves that configuration,
 * environment and container bootstrap complete.
 */
final class HealthController
{
    public function __construct(private readonly Application $application)
    {
    }

    /**
     * @return array{status: 'ok', framework: string, environment: string, booted: bool, php: string, timestamp: string}
     */
    public function payload(): array
    {
        /** @var string $frameworkName */
        $frameworkName = $this->application->config()->get('app.framework', 'SahilPHP');

        return [
            'status' => 'ok',
            'framework' => $frameworkName,
            'environment' => (string) $this->application->config()->get('app.environment', 'development'),
            'booted' => $this->application->isBooted(),
            'php' => PHP_VERSION,
            'timestamp' => gmdate('c'),
        ];
    }

    /**
     * Kernel-facing handler for the /health route: HTTP Response.
     */
    public function response(): \Sahil\Http\Response
    {
        return \Sahil\Http\Response::json($this->payload());
    }
}
