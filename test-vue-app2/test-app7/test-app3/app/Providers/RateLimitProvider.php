<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Container\Container;
use Sahil\Foundation\ServiceProvider;
use Sahil\Http\RateLimit\ArrayStore;
use Sahil\Http\RateLimit\RateLimiter;
use Sahil\Http\RateLimit\StoreInterface;
use Sahil\Http\Middleware\Throttle;

/**
 * Registers the rate-limiting service.
 *
 * Bindings (in order):
 *
 *   - `Sahil\Http\RateLimit\StoreInterface` → the shared counter backend
 *     (default: `Sahil\Http\RateLimit\ArrayStore`, process-local; swap it
 *     for a Redis/database driver in a real deployment).
 *   - `Sahil\Http\RateLimit\RateLimiter` → the gate service.
 *   - `Sahil\Http\Middleware\Throttle` → the route middleware, wired with
 *     the configured limit and window.
 *
 * All three read `rate_limit` from `config/app.php`. Override the store
 * by binding `Sahil\Http\RateLimit\StoreInterface` yourself after this
 * provider.
 */
final class RateLimitProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app()->config()->get('rate_limit', []);

        $default = $config['default'] ?? [];
        $limit = (int) ($default['limit'] ?? 60);
        $window = (int) ($default['window'] ?? 60);
        $storeClass = $config['store'] ?? ArrayStore::class;

        $store = new $storeClass($limit, $window);

        $this->app()->container()->singleton(StoreInterface::class, function () use ($store): StoreInterface {
            return $store;
        });

        $this->app()->container()->singleton(RateLimiter::class, function (Container $container) use ($store): RateLimiter {
            return new RateLimiter($container->make(StoreInterface::class));
        });

        $this->app()->container()->singleton(Throttle::class, function () use ($store): Throttle {
            return new Throttle('api', $store);
        });
    }
}
