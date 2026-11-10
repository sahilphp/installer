<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Container\Container;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Contracts\Queue\DispatcherContract;
use Sahil\Contracts\Queue\FailedJobRepositoryContract;
use Sahil\Contracts\Queue\QueueContract;
use Sahil\Database\Connection;
use Sahil\Foundation\ServiceProvider;
use Sahil\Queue\Failed\FailedJobRepository;
use Sahil\Queue\QueueManager;
use Sahil\Queue\Worker;

/**
 * Wires the queue layer.
 *
 * Everything is a lazy singleton: the queue connection (and therefore the
 * database) is only resolved when something is actually queued, dispatched
 * or worked. Configuration is read from `queue.*` in `config/queue.php`.
 */
final class QueueProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        $container->singleton(QueueManager::class, static function (Container $c): QueueManager {
            $configuration = $c->make(ApplicationContract::class)->config();

            return new QueueManager(
                $c->make(Connection::class),
                (array) $configuration->get('queue.connections', []),
                (string) $configuration->get('queue.default', 'database'),
            );
        });

        $container->singleton(
            DispatcherContract::class,
            static fn (Container $c): DispatcherContract => $c->make(QueueManager::class)
        );

        $container->singleton(
            QueueContract::class,
            static fn (Container $c): QueueContract => $c->make(QueueManager::class)->store()
        );

        $container->singleton(FailedJobRepositoryContract::class, static function (Container $c): FailedJobRepositoryContract {
            $table = (string) $c->make(ApplicationContract::class)->config()->get('queue.failed.table', 'failed_jobs');

            return new FailedJobRepository($c->make(Connection::class), $table);
        });

        $container->singleton(Worker::class, function (Container $c): Worker {
            $configuration = $c->make(ApplicationContract::class)->config();

            return new Worker(
                $c->make(QueueContract::class),
                $c->make(FailedJobRepositoryContract::class),
                (string) $configuration->get('queue.work.queue', QueueManager::DEFAULT_QUEUE),
                (int) $configuration->get('queue.retry.max_attempts', 3),
                (int) $configuration->get('queue.retry.delay', 0),
                (int) $configuration->get('queue.work.sleep', 1),
                null,
                $this->allowedJobClasses($configuration->get('queue.allowed_jobs')),
            );
        });
    }

    /**
     * Normalise the configured allow-list into class names.
     *
     * @return list<class-string>
     */
    private function allowedJobClasses(mixed $configured): array
    {
        if (!is_array($configured)) {
            return [];
        }

        $classes = [];

        foreach ($configured as $class) {
            if (is_string($class) && $class !== '') {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
