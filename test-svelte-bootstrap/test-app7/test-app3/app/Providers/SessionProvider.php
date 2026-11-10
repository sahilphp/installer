<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Container\Container;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Contracts\Session\SessionContract;
use Sahil\Contracts\Session\SessionDriverContract;
use Sahil\Foundation\ServiceProvider;
use Sahil\Http\Middleware\StartSession;
use Sahil\Http\Middleware\VerifyCsrfToken;
use Sahil\Session\FileSessionDriver;
use Sahil\Session\Session;

/**
 * Wires the session layer, session driver, and CSRF middleware.
 *
 * Registers the session driver (file-based by default), the session manager,
 * and the CSRF verification middleware as global middleware.
 *
 * Configuration read: the `session.*` namespace (`config/session.php`).
 */
final class SessionProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        // Register the session driver
        $container->singleton(SessionDriverContract::class, static function (Container $container): SessionDriverContract {
            $config = $container->make(ApplicationContract::class)->config();
            $driver = (string) $config->get('session.driver', 'file');

            if ($driver !== 'file') {
                throw new \RuntimeException(
                    sprintf('Unsupported session driver: %s. Only "file" is currently supported.', $driver)
                );
            }

            $path = (string) $config->get('session.path', storage_path('framework/sessions'));

            return new FileSessionDriver($path);
        });

        // Register the session manager
        $container->singleton(SessionContract::class, static function (Container $container): SessionContract {
            $config = $container->make(ApplicationContract::class)->config();

            $lifetime = (int) $config->get('session.lifetime', 120) * 60; // Convert minutes to seconds
            $secure = (bool) $config->get('session.secure', false);
            $httpOnly = (bool) $config->get('session.http_only', true);
            $sameSite = (string) $config->get('session.same_site', 'lax');

            $session = new Session(
                $container->make(SessionDriverContract::class),
                $lifetime,
                $secure,
                $httpOnly,
                $sameSite,
            );

            $cookieName = (string) $config->get('session.cookie', 'sahil_session');
            $session->setName($cookieName);

            return $session;
        });

        // Register session middleware
        $container->singleton(StartSession::class, static fn (Container $c): StartSession
            => new StartSession($c->make(SessionContract::class)));

        // Register CSRF middleware
        $container->singleton(VerifyCsrfToken::class, static function (Container $container): VerifyCsrfToken {
            $config = $container->make(ApplicationContract::class)->config();
            $except = (array) $config->get('session.csrf_except', []);

            return new VerifyCsrfToken(
                $container->make(SessionContract::class),
                $except
            );
        });
    }
}
