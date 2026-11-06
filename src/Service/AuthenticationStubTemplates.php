<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Service;

/**
 * Owns the internal authentication scaffolding templates used by
 * `sahilphp new`.
 *
 * These templates are internal to the installer package. The generated
 * application receives only the rendered authentication pages this installer
 * writes, never this directory.
 */
final class AuthenticationStubTemplates
{
    private const TEMPLATE_ROOT = __DIR__ . '/../resources/stubs/auth';

    public static function root(): string
    {
        return self::TEMPLATE_ROOT;
    }

    public static function sharedRoot(): string
    {
        return self::TEMPLATE_ROOT . '/shared';
    }

    public static function profileRoot(string $profile): string
    {
        return self::TEMPLATE_ROOT . '/' . $profile;
    }
}
