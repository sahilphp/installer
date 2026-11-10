<?php

declare(strict_types=1);

namespace App\Providers;

use RuntimeException;
use App\Auth\DatabasePasswordResetTokenRepository;
use App\Auth\UserRepository;
use Sahil\Auth\AuthManager;
use Sahil\Auth\DatabaseUserProvider;
use Sahil\Auth\EmailVerification;
use Sahil\Auth\Gate;
use Sahil\Auth\PasswordResetManager;
use Sahil\Auth\PolicyManager;
use Sahil\Auth\RateLimiter;
use Sahil\Auth\SessionAuthState;
use Sahil\Container\Container;
use Sahil\Contracts\Auth\PasswordResetTokenRepositoryContract;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Database\Connection;
use Sahil\Foundation\ServiceProvider;
use Sahil\Orm\Model;

/**
 * Wires the authentication and authorization layer.
 *
 * Registers the authentication manager, user providers, guards,
 * gates, policies, and related middleware.
 */
final class AuthProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        // Register rate limiter
        $container->singleton(RateLimiter::class, static fn (): RateLimiter => new RateLimiter());

        // Register database user provider
        $container->singleton(DatabaseUserProvider::class, static function (Container $container): DatabaseUserProvider {
            $application = $container->make(ApplicationContract::class);

            if (!$application instanceof ApplicationContract) {
                throw new RuntimeException('The application binding must resolve to the application contract.');
            }

            $config = $application->config();
            $connection = $container->make(Connection::class);

            if (!$connection instanceof Connection) {
                throw new RuntimeException('The authentication database binding must resolve to a database connection.');
            }

            $model = (string) $config->get('auth.providers.users.model', \Sahil\Auth\SimpleUser::class);

            if (is_a($model, Model::class, true)) {
                $model::useConnection($connection);
            }

            return new DatabaseUserProvider(
                $connection,
                (string) $config->get('auth.providers.users.table', 'users'),
                $model,
            );
        });

        // Register session auth state
        $container->singleton(SessionAuthState::class, static function (Container $container): SessionAuthState {
            $session = $container->make(\Sahil\Contracts\Session\SessionContract::class);
            $provider = $container->make(DatabaseUserProvider::class);

            return new SessionAuthState($session, $provider);
        });

        // Register auth manager
        $container->singleton(AuthManager::class, static function (Container $container): AuthManager {
            $config = $container->make(ApplicationContract::class)->config();
            $provider = $container->make(DatabaseUserProvider::class);
            $state = $container->make(SessionAuthState::class);
            $rateLimiter = $container->make(RateLimiter::class);

            $manager = new AuthManager($provider, $state, $rateLimiter);

            // Set password options from config
            $manager->setPasswordOptions([
                'algorithm' => $config->get('auth.password.algorithm', PASSWORD_BCRYPT),
                'cost' => (int) $config->get('auth.password.cost', 12),
            ]);

            return $manager;
        });

        // Register gate
        $container->singleton(Gate::class, static fn (): Gate => new Gate());

        // Register policy manager
        $container->singleton(PolicyManager::class, static fn (): PolicyManager => new PolicyManager());

        // Register email verification
        $container->singleton(EmailVerification::class, static fn (): EmailVerification => new EmailVerification());

        // Accounts: creation and lookup for the generated registration form.
        $container->singleton(UserRepository::class, static function (Container $container): UserRepository {
            $application = $container->make(ApplicationContract::class);

            if (!$application instanceof ApplicationContract) {
                throw new RuntimeException('The application binding must resolve to the application contract.');
            }

            $connection = $container->make(Connection::class);

            if (!$connection instanceof Connection) {
                throw new RuntimeException('The authentication database binding must resolve to a database connection.');
            }

            $config = $application->config();

            return new UserRepository(
                $connection,
                [
                    'algorithm' => $config->get('auth.password.algorithm', PASSWORD_BCRYPT),
                    'cost' => (int) $config->get('auth.password.cost', 12),
                ],
            );
        });

        // Password reset: tokens live in the database, expire after the
        // configured window, and only their SHA-256 digest is stored.
        $container->singleton(PasswordResetTokenRepositoryContract::class, static function (Container $container): PasswordResetTokenRepositoryContract {
            $config = $container->make(ApplicationContract::class)->config();

            return new DatabasePasswordResetTokenRepository(
                $container->make(\Sahil\Database\Connection::class),
                (int) $config->get('auth.password_reset.token_expiration', 60),
            );
        });

        $container->singleton(PasswordResetManager::class, static function (Container $container): PasswordResetManager {
            $config = $container->make(ApplicationContract::class)->config();

            return new PasswordResetManager(
                $container->make(DatabaseUserProvider::class),
                $container->make(PasswordResetTokenRepositoryContract::class),
                (int) $config->get('auth.password_reset.token_expiration', 60),
            );
        });

        // Register authentication middleware
        $container->singleton(\Sahil\Http\Middleware\Authenticate::class, static fn (Container $c): \Sahil\Http\Middleware\Authenticate
            => new \Sahil\Http\Middleware\Authenticate($c->make(AuthManager::class)));

        // Register authorization middleware
        $container->singleton(\Sahil\Http\Middleware\Authorize::class, static function (Container $container): \Sahil\Http\Middleware\Authorize {
            return new \Sahil\Http\Middleware\Authorize(
                $container->make(AuthManager::class),
                $container->make(Gate::class)
            );
        });
    }
}
