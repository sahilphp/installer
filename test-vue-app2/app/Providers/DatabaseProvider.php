<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Container\Container;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Database\Connection;
use Sahil\Database\ConnectionFactory;
use Sahil\Database\Schema\Schema;
use Sahil\Foundation\ServiceProvider;
use Sahil\Migrations\Confirmation;
use Sahil\Migrations\ConfirmationContract;
use Sahil\Migrations\MigrationLoader;
use Sahil\Migrations\MigrationLock;
use Sahil\Migrations\MigrationRepository;
use Sahil\Migrations\Migrator;
use Sahil\Migrations\MutationGuard;
use Sahil\Migrations\SeederLoader;
use Sahil\Migrations\SeederRunner;

/**
 * Wires the database layer, the schema builder and the migration/seed
 * services.
 *
 * Everything is registered as a LAZY singleton closure, so nothing connects
 * at boot: `php sahil about` and HTTP requests that never touch the database
 * keep working when the server is down or unconfigured. The connection is
 * established the first time it is actually resolved, and any resolution
 * failure surfaces as a `ConnectionException` (never a half-built service).
 *
 * `ConfirmationContract` is bound to {@see Confirmation}: interactive when
 * STDIN is a terminal, refusing otherwise. Tests and alternative front-ends
 * may override the binding before resolving {@see Migrator}.
 *
 * Configuration read: the `database.*` namespace (`config/database.php`).
 */
final class DatabaseProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        $container->singleton(ConnectionFactory::class, static fn (): ConnectionFactory => new ConnectionFactory());

        $container->singleton(Connection::class, static function (Container $container): Connection {
            /** @var ConnectionFactory $factory */
            $factory = $container->make(ConnectionFactory::class);

            /** @var array<string, mixed> $configuration */
            $configuration = (array) $container->make(ApplicationContract::class)
                ->config()
                ->get('database', []);

            return $factory->make($configuration);
        });

        $container->singleton(Schema::class, static fn (Container $c): Schema
            => new Schema($c->make(Connection::class)));

        $container->singleton(ConfirmationContract::class, static fn (): Confirmation => new Confirmation());

        $container->singleton(MigrationLoader::class, static function (Container $c): MigrationLoader {
            $application = $c->make(ApplicationContract::class);

            return new MigrationLoader(
                $application->basePath((string) $application->config()->get('database.migrations', 'database/migrations'))
            );
        });

        $container->singleton(SeederLoader::class, static function (Container $c): SeederLoader {
            $application = $c->make(ApplicationContract::class);

            return new SeederLoader(
                $application->basePath((string) $application->config()->get('database.seeders', 'database/seeders'))
            );
        });

        $container->singleton(MigrationRepository::class, static fn (Container $c): MigrationRepository
            => new MigrationRepository($c->make(Connection::class)));

        $container->singleton(MigrationLock::class, static function (Container $c): MigrationLock {
            $config = $c->make(ApplicationContract::class)->config();

            // MySQL's GET_LOCK namespace is global to the server, so the
            // database name is folded in to keep databases independent.
            $name = (string) $config->get('database.lock', 'sahil_migrations')
                .':'.(string) $config->get('database.database', 'default');

            return new MigrationLock(
                $c->make(Connection::class),
                $name,
                (int) $config->get('database.lock_timeout', 10)
            );
        });

        $container->singleton(MutationGuard::class, static function (Container $c): MutationGuard {
            $config = $c->make(ApplicationContract::class)->config();

            return new MutationGuard(
                (string) $config->get('app.environment', 'development'),
                $c->make(ConfirmationContract::class)
            );
        });

        $container->singleton(Migrator::class, static fn (Container $c): Migrator => new Migrator(
            $c->make(Connection::class),
            $c->make(MigrationRepository::class),
            $c->make(MigrationLoader::class),
            $c->make(MutationGuard::class),
            $c->make(MigrationLock::class),
        ));

        $container->singleton(SeederRunner::class, static fn (Container $c): SeederRunner => new SeederRunner(
            $c->make(Connection::class),
            $c->make(SeederLoader::class),
        ));
    }
}
