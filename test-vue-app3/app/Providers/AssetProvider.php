<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Assets\AssetManager;
use Sahil\Container\Container;
use Sahil\Contracts\Assets\AssetManagerContract;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Foundation\ServiceProvider;

/**
 * Wires the runtime asset manager.
 *
 * Configuration is read from the `assets.*` namespace. The manager is
 * resolved lazily (it is a singleton, but nothing reads the profile or the
 * build directory until a template or controller asks for an asset), so a
 * CLI command or API route that never renders HTML pays no filesystem cost.
 */
final class AssetProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        $container->singleton(AssetManager::class, static function (Container $container): AssetManager {
            $application = $container->make(ApplicationContract::class);
            $config = $application->config();

            return new AssetManager(
                basePath: $application->basePath(),
                profile: (string) $config->get('assets.profile', 'auto'),
                mode: (string) $config->get('assets.mode', 'auto'),
                environment: (string) $config->get('app.environment', 'development'),
                devServer: (string) $config->get('assets.dev_server', 'http://127.0.0.1:5173'),
                baseUrl: (string) $config->get('assets.base_url', ''),
                buildPath: (string) $config->get('assets.build_path', 'public/build'),
                buildPublicPath: (string) $config->get('assets.build_public_path', 'build'),
                verify: (bool) $config->get('assets.verify', true),
            );
        });

        $container->singleton(
            AssetManagerContract::class,
            static fn (Container $container): AssetManager => $container->make(AssetManager::class),
        );

        // Ergonomics: `$container->make('assets')` in application code.
        $container->alias('assets', AssetManagerContract::class);
    }
}
