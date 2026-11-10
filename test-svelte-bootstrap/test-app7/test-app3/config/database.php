<?php

declare(strict_types=1);

/**
 * Database configuration.
 *
 * Values come from the environment (see `.env.example`); real credentials
 * never belong in this file or in source control. The `password` key path is
 * recognised by the redactor, so `config:show database` prints `[redacted]`
 * instead of the secret.
 *
 * SQLite is the default so a fresh project can migrate and run with no
 * database server at all. Drivers: `sqlite` (needs `pdo_sqlite`), `mysql`
 * (needs `pdo_mysql`) and `pgsql` (needs `pdo_pgsql`). The server drivers are
 * gated by integration tests — see the README "Database setup".
 */

$driver = (string) env('DB_DRIVER', 'sqlite');

// SQLite stores everything in one file. Empty DB_DATABASE means the file
// inside the project; the connection factory creates it on first connect.
$sqliteDatabase = dirname(__DIR__).'/database/database.sqlite';
$database = trim((string) env('DB_DATABASE', ''));

if ($database === '') {
    $database = $driver === 'sqlite' ? $sqliteDatabase : 'sahil';
}

return [
    'driver' => $driver,
    'host' => (string) env('DB_HOST', '127.0.0.1'),
    'port' => (int) env('DB_PORT', $driver === 'pgsql' ? 5432 : 3306),
    'database' => $database,
    'username' => (string) env('DB_USERNAME', ''),
    'password' => (string) env('DB_PASSWORD', ''),
    'charset' => (string) env('DB_CHARSET', 'utf8mb4'),

    // Where migrations and seeders live, relative to the application base path.
    'migrations' => 'database/migrations',
    'seeders' => 'database/seeders',

    // Advisory-lock name for migration runs. MySQL's GET_LOCK namespace is
    // global to the server, so the database name is appended at runtime to
    // keep separate databases independent; PostgreSQL advisory locks are
    // already per-database, and SQLite needs no lock.
    'lock' => 'sahil_migrations',
    'lock_timeout' => 10,
];
