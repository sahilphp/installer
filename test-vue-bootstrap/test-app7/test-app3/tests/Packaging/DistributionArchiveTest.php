<?php

declare(strict_types=1);

namespace Tests\Packaging;

use PHPUnit\Framework\TestCase;

/**
 * Guards files that the installed application reads at runtime.
 *
 * The starter's Composer archive is filtered through .gitattributes, so a
 * broad export-ignore can make source tests pass while a freshly installed app
 * is missing a runtime resource.
 */
final class DistributionArchiveTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function test_auth_scaffolds_are_not_export_ignored(): void
    {
        $attributes = (string) file_get_contents($this->root.'/.gitattributes');

        self::assertDoesNotMatchRegularExpression(
            '/^\/\.sahil(?:\/scaffolds(?:\/.*)?)?\s+export-ignore$/m',
            $attributes,
            'The installed app needs .sahil/scaffolds for unda:auth; do not exclude it from the archive.',
        );

        $composer = json_decode((string) file_get_contents($this->root.'/composer.json'), true);
        $excluded = $composer['archive']['exclude'] ?? [];

        self::assertIsArray($excluded);
        self::assertNotContains('/.sahil', $excluded, 'The Composer archive must include auth scaffolds.');
        self::assertNotContains('.sahil', $excluded, 'The Composer archive must include auth scaffolds.');
    }

    public function test_public_entry_point_is_root_sahil_and_not_bin_sahil(): void
    {
        self::assertFileExists($this->root.'/sahil');
        self::assertDirectoryDoesNotExist($this->root.'/bin');

        $entrypoint = (string) file_get_contents($this->root.'/sahil');
        self::assertStringContainsString('Sahil\\Console\\Application', $entrypoint);
        self::assertStringNotContainsString('Sahil\\Console\\Commands\\GeneratorCommand', $entrypoint);
    }

    public function test_authentication_migrations_belong_to_unda_auth_only(): void
    {
        foreach ([
            '2026_10_30_000000_create_users_table.php',
            '2026_10_30_000001_create_password_reset_tokens_table.php',
            '2026_10_30_000002_create_sessions_table.php',
        ] as $migration) {
            self::assertFileDoesNotExist(
                $this->root.'/database/migrations/'.$migration,
                'unda:auth owns this migration; shipping it makes unda:migration collide.',
            );
            self::assertFileExists(
                $this->root.'/.sahil/scaffolds/auth/shared/database/migrations/'.$migration,
            );
        }
    }

    public function test_firefiber_starter_assets_and_manifests_are_present(): void
    {
        self::assertFileExists($this->root.'/config/firefiber.php');
        self::assertFileExists($this->root.'/bootstrap/commands.php');

        $layout = (string) file_get_contents($this->root.'/stubs/ui-kits/firefiber/resources/views/layouts/app.sahil.php');
        self::assertStringContainsString("@asset_css('@fiberstyles')", $layout);
        self::assertStringContainsString("@asset_js('@fiberscripts')", $layout);
        self::assertStringContainsString('window.Firefiber.boot()', (string) file_get_contents($this->root.'/stubs/ui-kits/firefiber/resources/js/app.js'));
        self::assertStringContainsString('FIREFIBER_ACTION_PATH', (string) file_get_contents($this->root.'/.env.example'));
    }

    public function test_auth_scaffolds_are_present_for_both_css_profiles(): void
    {
        $auth = $this->root.'/.sahil/scaffolds/auth';

        foreach (['shared', 'tailwind', 'bootstrap'] as $profile) {
            self::assertDirectoryExists($auth.'/'.$profile, $profile.' auth scaffolds are missing.');
        }

        foreach ([
            'shared/app/Models/User.php',
            'shared/routes/auth.php',
            'tailwind/resources/views/auth/login.sahil.php',
            'bootstrap/resources/views/auth/login.sahil.php',
        ] as $relative) {
            self::assertFileExists($auth.'/'.$relative, $relative.' is missing.');
        }
    }
}
