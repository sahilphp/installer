<?php

declare(strict_types=1);

namespace Tests\Bootstrap;

use PHPUnit\Framework\TestCase;
use Sahil\Foundation\Application;

/**
 * Smoke test for the starter-kit's supported application bootstrap.
 */
final class ApplicationBootstrapTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::free();

        parent::tearDown();
    }

    public function test_bootstrap_returns_a_booted_application_rooted_at_the_starter_kit(): void
    {
        $bootstrap = require_once dirname(__DIR__, 2).'/bootstrap/app.php';

        // `require_once` returns true when another test loaded the bootstrap
        // first, so resolve the documented application result either way.
        $application = $bootstrap instanceof Application ? $bootstrap : sahil_bootstrap();

        self::assertInstanceOf(
            Application::class,
            $application,
            'bootstrap/app.php must provide a booted Sahil\\Foundation\\Application.'
        );
        self::assertSame(
            $application,
            sahil_bootstrap(),
            'sahil_bootstrap() must return the same application instance.'
        );
        self::assertTrue(
            $application->isBooted(),
            'The application returned by bootstrap/app.php must be booted.'
        );
        self::assertSame(
            dirname(__DIR__, 2),
            $application->basePath(),
            'The application base path must be the starter-kit root.'
        );
    }
}
