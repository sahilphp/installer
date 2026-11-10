<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Foundation\ServiceProvider;
use Sahil\Http\TrustedProxy;

/**
 * Registers `Sahil\Http\TrustedProxy` from `config/app.php`.
 *
 * When `trusted_proxies.proxies` is empty (the default) the proxy is a
 * no-op for client IP and scheme resolution: `REMOTE_ADDR` and the
 * `HTTPS`/`REQUEST_SCHEME` server variables are used, and forwarded
 * headers are never trusted.
 */
final class ProxyProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app()->config()->get('trusted_proxies', []);

        $this->app()->container()->singleton(
            TrustedProxy::class,
            static function () use ($config): TrustedProxy {
                return TrustedProxy::fromConfig($config);
            }
        );
    }
}
