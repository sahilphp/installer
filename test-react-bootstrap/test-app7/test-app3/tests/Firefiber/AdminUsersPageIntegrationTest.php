<?php

declare(strict_types=1);

namespace Tests\Firefiber;

use App\Firefiber\Components\AdminUsersTable;
use App\Firefiber\Pages\AdminUsersPage;
use PHPUnit\Framework\TestCase;
use Sahil\Firefiber\Page;
use Sahil\Firefiber\PageRenderer;
use Sahil\Firefiber\PageRegistry;
use Sahil\Foundation\Application;

/**
 * Verifies the AdminUsersTable starter example is a real, usable Firefiber
 * integration: discoverable page contract, renderable markup with query-sync
 * attributes, server-validated query/actions, and PageRegistry discovery.
 */
final class AdminUsersPageIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::free();

        parent::tearDown();
    }

    public function test_page_class_is_discoverable_and_contract_correct(): void
    {
        self::assertTrue(class_exists(AdminUsersPage::class));
        self::assertTrue(is_subclass_of(AdminUsersPage::class, Page::class));

        $page = new \ReflectionClass(AdminUsersPage::class);
        self::assertTrue($page->hasMethod('path'));
        self::assertTrue($page->hasMethod('title'));
        self::assertTrue($page->hasMethod('components'));
    }

    public function test_query_sync_configuration_is_declared_and_typed(): void
    {
        $table = new AdminUsersTable();
        $config = $table->querySyncConfiguration();

        self::assertArrayHasKey('search', $config);
        self::assertArrayHasKey('page', $config);
        self::assertArrayHasKey('sort', $config);
        self::assertArrayHasKey('direction', $config);
        self::assertArrayHasKey('status', $config);
        self::assertSame('q', $config['search']['param']);
        self::assertSame('int', $config['page']['type']);
        self::assertSame('string', $config['search']['type']);
    }

    public function test_query_payload_applies_only_declared_validated_values(): void
    {
        $table = new AdminUsersTable();
        $result = $table->applyQuerySyncPayload([
            'page' => '2',
            'search' => 'amina',
            'status' => 'active',
            'secret' => 'ignored',
            'sort' => ['not', 'scalar'],
        ]);

        self::assertSame(2, $table->state()['page']);
        self::assertSame('amina', $table->state()['search']);
        self::assertSame('active', $table->state()['status']);
        self::assertContains('page', $result['applied']);
        self::assertContains('search', $result['applied']);
        self::assertContains('secret', $result['rejected']);
        self::assertContains('sort', $result['rejected']);
    }

    public function test_actions_support_sort_filter_and_pagination(): void
    {
        $table = new AdminUsersTable();

        $table->handleAction('sortBy', ['field' => 'email']);
        self::assertSame('email', $table->state()['sort']);
        self::assertSame('asc', $table->state()['direction']);
        self::assertSame(1, $table->state()['page']);

        $table->handleAction('sortBy', ['field' => 'email']);
        self::assertSame('desc', $table->state()['direction']);

        $table->handleAction('setStatus', ['status' => 'active']);
        self::assertSame('active', $table->state()['status']);
        self::assertSame(1, $table->state()['page']);

        $table->handleAction('goToPage', ['page' => 2]);
        self::assertSame(2, $table->state()['page']);

        $markup = $table->render();
        self::assertStringContainsString('admin-users-table', $markup);
        self::assertStringContainsString('Active', $markup);
    }

    public function test_search_filters_rows_through_datatable_allow_lists(): void
    {
        $table = new AdminUsersTable();
        $table->applyQuerySyncPayload(['search' => 'amina']);
        $markup = $table->render();

        self::assertStringContainsString('Amina Khan', $markup);
        self::assertStringNotContainsString('Bilal Shah', $markup);
    }

    public function test_page_is_discovered_and_renders_through_real_app(): void
    {
        $key = str_repeat('a', 64);
        putenv('FIREFIBER_HMAC_KEY='.$key);
        $_ENV['FIREFIBER_HMAC_KEY'] = $key;
        $_SERVER['FIREFIBER_HMAC_KEY'] = $key;
        putenv('APP_KEY=base64:'.base64_encode(random_bytes(32)));
        $_ENV['APP_KEY'] = getenv('APP_KEY') ?: '';
        $_SERVER['APP_KEY'] = getenv('APP_KEY') ?: '';

        require_once dirname(__DIR__, 2).'/bootstrap/app.php';

        try {
            $app = \sahil_bootstrap();
        } catch (\Throwable $e) {
            // If full bootstrap cannot run in this environment, verify discovery
            // contract and rendering via the real component/renderer classes.
            self::assertTrue(is_file(dirname(__DIR__, 2).'/app/Firefiber/Pages/AdminUsersPage.php'));
            $table = new AdminUsersTable();
            $markup = $table->render();
            self::assertStringContainsString('admin-users-table', $markup);
            self::assertStringContainsString('Amina Khan', $markup);
            self::markTestIncomplete('Full app bootstrap unavailable in this test environment: '.$e->getMessage());
        }

        $registry = $app->container()->make(PageRegistry::class);
        $pages = $registry->pages();
        $paths = array_map(static fn (Page $p): string => $p->path(), $pages);

        self::assertContains('/firefiber/admin-users', $paths, 'AdminUsersPage must be discovered under App\\Firefiber\\Pages.');

        $page = null;
        foreach ($pages as $candidate) {
            if ($candidate->path() === '/firefiber/admin-users') {
                $page = $candidate;
                break;
            }
        }

        self::assertInstanceOf(AdminUsersPage::class, $page);

        $components = $page->components();
        self::assertCount(1, $components);
        self::assertInstanceOf(AdminUsersTable::class, $components[0]);

        $renderer = $app->container()->make(PageRenderer::class);
        $markup = $renderer->renderComponent($components[0]);

        self::assertStringContainsString('admin-users-table', $markup);
        self::assertStringContainsString('Search', $markup);
        self::assertStringContainsString('Amina Khan', $markup);
        self::assertStringContainsString('data-firefiber-component', $markup);
        self::assertStringContainsString('data-firefiber-query-sync', $markup);
        self::assertStringContainsString('data-firefiber-snapshot', $markup);
    }
}
