<?php

declare(strict_types=1);

namespace App\Providers;

use RuntimeException;
use Sahil\Container\Container;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Contracts\Scheduler\ClockContract;
use Sahil\Contracts\Scheduler\LockContract;
use Sahil\Database\Connection;
use Sahil\Foundation\ServiceProvider;
use Sahil\Scheduler\DatabaseLock;
use Sahil\Scheduler\FileLock;
use Sahil\Scheduler\Schedule;
use Sahil\Scheduler\ScheduleRunner;
use Sahil\Scheduler\SystemClock;
use Sahil\Scheduler\TimeoutGuard;
use Sahil\Support\Logging\LoggerContract;

/**
 * Wires the task scheduler.
 *
 * The schedule itself is declarative: `routes/schedule.php` returns a
 * callable receiving the {@see Schedule} registry. Everything here is lazy,
 * so a request that never touches the scheduler never opens a lock file or
 * a database connection. Configuration comes from `schedule.*`; see
 * `config/schedule.php`.
 */
final class ScheduleProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        $container->singleton(ClockContract::class, static fn (): SystemClock => new SystemClock());

        $container->singleton(Schedule::class, function (Container $c): Schedule {
            $configuration = $c->make(ApplicationContract::class)->config();

            $schedule = new Schedule((string) $configuration->get('schedule.timezone', 'UTC'));

            $definition = dirname(__DIR__, 2).'/routes/schedule.php';

            if (!is_file($definition)) {
                return $schedule;
            }

            /** @var mixed $callback */
            $callback = require $definition;

            if (!is_callable($callback)) {
                throw new RuntimeException(
                    'routes/schedule.php must return a callable that receives the Sahil\\Scheduler\\Schedule.'
                );
            }

            $callback($schedule);

            return $schedule;
        });

        $container->singleton(LockContract::class, static function (Container $c): LockContract {
            $application = $c->make(ApplicationContract::class);
            $configuration = $application->config();

            $driver = (string) $configuration->get('schedule.lock.driver', 'file');

            if ($driver === 'file') {
                return new FileLock((string) $configuration->get(
                    'schedule.lock.path',
                    $application->storagePath('framework/schedule'),
                ));
            }

            if ($driver === 'database') {
                $prefix = (string) $configuration->get('schedule.lock.prefix', 'sahil_schedule');
                $database = (string) $configuration->get('database.database', 'default');

                return new DatabaseLock($c->make(Connection::class), $prefix.':'.$database);
            }

            throw new RuntimeException(sprintf(
                'Unsupported schedule lock driver [%s]; use [file] or [database].',
                $driver
            ));
        });

        $container->singleton(TimeoutGuard::class, static fn (): TimeoutGuard => new TimeoutGuard());

        $container->singleton(ScheduleRunner::class, static fn (Container $c): ScheduleRunner => new ScheduleRunner(
            $c->make(Schedule::class),
            $c->make(ClockContract::class),
            $c->make(LockContract::class),
            $c->has(LoggerContract::class) ? $c->make(LoggerContract::class) : null,
            $c->make(TimeoutGuard::class),
        ));
    }
}
