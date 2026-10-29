<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Service;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Handles cleanup of partial or failed project directories.
 */
final class Cleaner
{
    /**
     * Remove a directory and all its contents.
     * Only removes directories that the installer created.
     *
     * @param string $directory The directory to remove
     * @return bool True if successfully removed, false if directory didn't exist or couldn't be removed
     */
    public static function remove(string $directory): bool
    {
        if (!is_dir($directory)) {
            return true; // Nothing to remove
        }

        $iterator = new RecursiveDirectoryIterator(
            $directory,
            RecursiveDirectoryIterator::SKIP_DOTS
        );
        $ri = new RecursiveIteratorIterator(
            $iterator,
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($ri as $file) {
            $path = $file->getPathname();

            if ($file->isDir()) {
                // Skip .git directory - never remove it automatically
                if ($file->getFilename() === '.git') {
                    continue;
                }
                // Attempt to remove directory
                if (!@rmdir($path)) {
                    // Try recursive remove
                    @system('rm -rf '.$path);
                }
            } else {
                // Attempt to remove file
                @unlink($path);
            }
        }

        // Final attempt to remove the directory itself
        return @rmdir($directory) || @system('rm -rf '.$directory);
    }

    /**
     * Check if a directory appears to be an installer-created project.
     *
     * @param string $directory The directory to check
     * @return bool True if the directory looks like an installer project
     */
    public static function isInstallerProject(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $checks = [
            is_file($directory.'/bin/sahil'),
            is_file($directory.'/composer.json'),
            is_dir($directory.'/resources/views'),
        ];

        return array_sum($checks) >= 2;
    }
}