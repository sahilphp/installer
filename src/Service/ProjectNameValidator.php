<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Service;

final class ProjectNameValidator
{
    public function isValid(string $name): bool
    {
        // Reject empty names
        if ($name === '' || $name === null) {
            return false;
        }

        // Reject path traversal and spaces
        if (str_contains($name, ' ')
            || str_contains($name, '..')
            || str_contains($name, '/')
            || str_contains($name, '\\')) {
            return false;
        }

        // Allow only letters, numbers, dashes, underscores
        return preg_match('/^[A-Za-z0-9_-]+$/', $name) === 1;
    }

    /** @return list<string> */
    public function getErrors(string $name): array
    {
        $errors = [];

        if ($name === '' || $name === null) {
            $errors[] = 'Project name cannot be empty';
        }

        if (str_contains($name, ' ')) {
            $errors[] = 'Project name cannot contain spaces';
        }

        if (str_contains($name, '..')) {
            $errors[] = 'Project name cannot contain ".."';
        }

        if (str_contains($name, '/') || str_contains($name, '\\')) {
            $errors[] = 'Project name cannot contain path separators';
        }

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
            $errors[] = 'Project name must contain only letters, numbers, dashes, and underscores';
        }

        return $errors;
    }
}
