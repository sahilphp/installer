<?php

declare(strict_types=1);

/**
 * Application bootstrap.
 *
 * Roots the application at the project directory, loads the `.env` file,
 * loads configuration from `config/`, registers application service
 * providers and boots them.
 *
 * Consumers must include this file with `require_once` (it defines the
 * sahil_bootstrap() function) and receive the booted Application.
 */

use Sahil\Contracts\Foundation\ApplicationContract;
use Sahil\Foundation\Application;
use Sahil\Foundation\ProviderManifest;
use Sahil\Foundation\ServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Build (once per process) and return the booted Application for this
 * installation.
 */
function sahil_bootstrap(): Application
{
    static $application = null;

    if ($application instanceof Application) {
        return $application;
    }

    $basePath = dirname(__DIR__);

    Application::free();
    $application = Application::configure($basePath);

    $application
        ->loadEnvironment('.env')
        ->loadConfiguration();

    foreach (sahil_application_providers($application) as $provider) {
        $application->register($provider);
    }

    $application->boot();

    return $application;
}

/**
 * Service providers for this installation, in registration order.
 *
 * The provider classes live in `bootstrap/providers.php` so tests can build
 * the same application on an isolated base path without booting.
 *
 * @return list<ServiceProvider>
 */
function sahil_application_providers(ApplicationContract $application): array
{
    return ProviderManifest::load(__DIR__.'/providers.php', $application);
}

return sahil_bootstrap();
