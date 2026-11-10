<?php

declare(strict_types=1);

namespace App\Firefiber\Pages;

use App\Firefiber\Components\AdminUsersTable;
use Sahil\Firefiber\Page;

/**
 * Discoverable page that renders the AdminUsersTable starter example.
 *
 * Exercises URL-synchronized, server-validated table state (search, sort,
 * filters, pagination) through a real Firefiber page route.
 */
final class AdminUsersPage extends Page
{
    public function path(): string
    {
        return '/firefiber/admin-users';
    }

    public function title(): string
    {
        return 'Admin Users';
    }

    public function components(): array
    {
        return [new AdminUsersTable()];
    }
}
