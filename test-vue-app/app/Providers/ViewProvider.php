<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Container\Container;
use Sahil\Contracts\Assets\AssetManagerContract;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Contracts\View\ViewContract;
use Sahil\Foundation\ServiceProvider;
use Sahil\View\TemplateCompiler;
use Sahil\View\TemplateResolver;
use Sahil\View\ViewEngine;

/**
 * Wires the template engine: resolver (configured view roots), compiler
 * (compiled-template cache) and the engine itself.
 *
 * Configuration read: the `view.*` namespace (`config/view.php`). Relative
 * paths resolve against the application base path, so a deployment only ever
 * names directories.
 *
 * Nothing here touches the filesystem at boot — no view directory is read
 * and no cache is written until a template is actually rendered — so a CLI
 * command or an API route that never renders a view pays no cost and cannot
 * fail because of view setup.
 */
final class ViewProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        $container->singleton(TemplateResolver::class, static function (Container $container): TemplateResolver {
            $application = $container->make(ApplicationContract::class);
            $config = $application->config();

            /** @var list<string> $configured */
            $configured = (array) $config->get('view.paths', ['resources/views']);

            $roots = [];

            foreach ($configured as $path) {
                $path = (string) $path;

                if ($path === '') {
                    continue;
                }

                $roots[] = self::isAbsolute($path) ? rtrim($path, '/') : $application->basePath($path);
            }

            if ($roots === []) {
                throw new \Sahil\View\ViewException(
                    'No view directories are configured; set view.paths in config/view.php.'
                );
            }

            /** @var string $extension */
            $extension = (string) $config->get('view.extension', '.sahil.php');

            return new TemplateResolver($roots, $extension);
        });

        $container->singleton(TemplateCompiler::class, static function (Container $container): TemplateCompiler {
            $application = $container->make(ApplicationContract::class);

            /** @var string $cache */
            $cache = (string) $application->config()->get('view.cache', 'storage/framework/views');

            return new TemplateCompiler(
                self::isAbsolute($cache) ? rtrim($cache, '/') : $application->basePath($cache)
            );
        });

        $container->singleton(ViewContract::class, static function (Container $container): ViewEngine {
            /** @var TemplateResolver $resolver */
            $resolver = $container->make(TemplateResolver::class);

            /** @var TemplateCompiler $compiler */
            $compiler = $container->make(TemplateCompiler::class);

            // AssetProvider registers before this provider in the application
            // manifest. Keep the fallback so a unit test can build a view-only
            // container without the asset subsystem.
            $assets = $container->bound(AssetManagerContract::class)
                ? $container->make(AssetManagerContract::class)
                : null;

            return new ViewEngine($resolver, $compiler, $assets);
        });

        // Ergonomics: `$container->make('view')` for the common case.
        $container->alias('view', ViewContract::class);
    }

    /**
     * Whether a configured path is absolute (POSIX or Windows), in which case
     * it is used verbatim instead of being joined to the base path.
     */
    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1
            || str_starts_with($path, '\\\\');
    }
}
