<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Cache\FileCache;
use Sahil\Cache\RedisCache;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Contracts\Cache\CacheContract;
use Sahil\Foundation\ServiceProvider;

/**
 * Registers the cache service.
 *
 * Binds `Sahil\Contracts\Cache\CacheContract` to a cache driver instance,
 * configured via `config/cache.php`.
 *
 * Supported drivers: file, redis
 *
 * To use a different driver, set `CACHE_DRIVER` env variable or
 * override the binding after this provider has been registered.
 */
final class CacheProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app()->config()->get('cache', []);

        $default = $config['default'] ?? 'file';
        $stores  = $config['stores']  ?? [];

        $driver = $stores[$default]['driver'] ?? 'file';

        // Build cache instance based on driver
        switch (strtolower($driver)) {
            case 'redis':
                $options = $stores[$default]['options'] ?? [];
                // Only use Redis if the extension is available
                if (extension_loaded('redis')) {
                    $cache = new RedisCache($options);
                } else {
                    // Fall back to file cache if redis extension not loaded
                    $cache = new FileCache(storage_path('framework/cache'));
                }
                break;

            case 'file':
            default:
                $cachePath = $stores[$default]['path'] ?? storage_path('framework/cache');
                $cache = new FileCache($cachePath);
                break;
        }

        $this->app()->container()->singleton(
            CacheContract::class,
            static fn (): CacheContract => $cache
        );
    }
}