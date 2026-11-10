<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Container\Container;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Contracts\Mail\MailerContract;
use Sahil\Contracts\Queue\DispatcherContract;
use Sahil\Foundation\ServiceProvider;
use Sahil\Mail\MailManager;
use Sahil\Notifications\Channels\MailChannel;
use Sahil\Notifications\Notifier;
use Sahil\Support\Logging\LoggerContract;

/**
 * Wires mail and notifications.
 *
 * Everything is lazy: no transport is built (and no socket or credential is
 * touched) until a message is actually sent. The queue dispatcher is passed
 * as a factory too, so a synchronous send never opens a database connection.
 *
 * Configuration comes from `mail.*` in `config/mail.php`:
 * - `default` and `transports.*` choose and configure delivery;
 * - `from` is the sender identity applied to messages that name none;
 * - `queue` controls optional deferred delivery (`MAIL_QUEUE`).
 */
final class MailProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app()->container();

        $container->singleton(MailManager::class, static function (Container $c): MailManager {
            $configuration = $c->make(ApplicationContract::class)->config();

            return new MailManager(
                (array) $configuration->get('mail.transports', []),
                (string) $configuration->get('mail.default', 'log'),
                (array) $configuration->get('mail.from', []),
                (bool) $configuration->get('mail.queue.enabled', false),
                (string) $configuration->get('mail.queue.name', 'emails'),
                (int) $configuration->get('mail.queue.delay', 0),
                $c->has(LoggerContract::class) ? $c->make(LoggerContract::class) : null,
                static fn (): DispatcherContract => $c->make(DispatcherContract::class),
            );
        });

        $container->alias('mail', MailManager::class);
        $container->alias('mailer', MailManager::class);
        $container->alias(MailerContract::class, MailManager::class);

        $container->singleton(Notifier::class, static function (Container $c): Notifier {
            $configuration = $c->make(ApplicationContract::class)->config();

            return new Notifier(
                [new MailChannel(
                    $c->make(MailerContract::class),
                    (bool) $configuration->get('mail.queue.enabled', false),
                    (int) $configuration->get('mail.queue.delay', 0),
                )],
                $c->has(LoggerContract::class) ? $c->make(LoggerContract::class) : null,
            );
        });

        $container->alias('notifier', Notifier::class);
    }
}
