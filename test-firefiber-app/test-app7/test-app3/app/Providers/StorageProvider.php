<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Container\Container;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Contracts\Storage\StorageContract;
use Sahil\Foundation\ServiceProvider;
use Sahil\Storage\StorageManager;

/**
 * Wires the storage layer.
 *
 * Registers the storage manager with disks from the `storage.*`
 * configuration namespace (see `config/storage.php`). Remote storage
 * adapters can be plugged in at runtime through
 * {@see StorageManager::extend()}.
 */
final class StorageProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        $container->singleton(StorageManager::class, static function (Container $container): StorageManager {
            $application = $container->make(ApplicationContract::class);

            // The document root is passed so a private disk configured inside
            // it is refused instead of silently becoming web-accessible.
            return new StorageManager($application->config(), $application->basePath('public'));
        });

        $container->alias('storage', StorageManager::class);
        $container->alias(StorageContract::class, StorageManager::class);
    }
}
