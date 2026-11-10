<?php

declare(strict_types=1);

namespace App\Providers;

use Sahil\Container\Container;
use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Encryption\Encrypter;
use Sahil\Foundation\ServiceProvider;

/**
 * Binds the authenticated encrypter to `APP_KEY`.
 *
 * The binding is lazy: an application without a key still boots and serves
 * pages, and only resolving `Encrypter` reports that the key is missing. The
 * configured value comes from `config/app.php` (`key`, read from `APP_KEY`),
 * and `php sahil key:generate` writes it.
 *
 * Usage: `$app->container()->make(Encrypter::class)` then
 * `encryptString()`/`decryptString()` for confidential values, or
 * `sign()`/`verify()` for tamper-evident cookies and URLs.
 */
final class EncryptionProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->container()->singleton(
            Encrypter::class,
            static function (Container $container): Encrypter {
                $config = $container->make(ApplicationContract::class)->config();
                $key = $config->get('app.key');

                return Encrypter::fromConfiguration(is_string($key) ? $key : null);
            }
        );
    }
}
