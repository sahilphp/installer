<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Foundation\CentralExceptionHandler;
use Sahil\Foundation\ServiceProvider;
use Sahil\Http\Kernel;
use Sahil\Http\Middleware\SecurityHeaders;
use Sahil\Support\Logging\FileLogger;
use Sahil\Support\Logging\LoggerContract;

/**
 * Registers observability services: the structured file logger and the
 * centralized exception handler, and installs the handler into the HTTP
 * Kernel so every uncaught throwable is both logged and rendered safely.
 *
 * Configuration this provider reads:
 * - `app.debug` (bool) — MUST be false in production (validated at boot);
 * - `app.log_level` (string, default `info`) — lowest severity kept;
 * - `app.environment` — the environment the exception handler reports in.
 */
final class ObservabilityProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        $container->singleton(
            LoggerContract::class,
            static function (\Sahil\Container\Container $c): LoggerContract {
                $app = $c->make(\Sahil\Contracts\Foundation\ApplicationContract::class);

                return new FileLogger(
                    $app->storagePath('logs/app.log'),
                    (string) $app->config()->get('app.log_level', 'info')
                );
            }
        );

        $container->singleton(
            CentralExceptionHandler::class,
            static fn (\Sahil\Container\Container $c): CentralExceptionHandler
                => new CentralExceptionHandler($c->make(LoggerContract::class))
        );
    }

    public function boot(): void
    {
        // Attach the centralized handler to the kernel (if one is bound).
        $container = $this->app()->container();

        if ($container->has(Kernel::class)) {
            /** @var Kernel $kernel */
            $kernel = $container->make(Kernel::class);

            // Secure response headers are added for every request, before the
            // exception handler is attached so error responses get them too.
            $kernel = $kernel->using(new SecurityHeaders());

            $handler = $container->make(CentralExceptionHandler::class);
            $debug = $container->make(\Sahil\Contracts\Foundation\ApplicationContract::class)
                ->config()->get('app.debug') === true;

            $container->instance(Kernel::class, $kernel->usingExceptionHandler(
                static function (\Throwable $throwable) use ($handler, $debug): ?\Sahil\Http\Response {
                    $handler->report($throwable);

                    return $handler->render($throwable, $debug);
                }
            ));
        }
    }
}
