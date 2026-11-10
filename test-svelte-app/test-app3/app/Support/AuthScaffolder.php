<?php

declare(strict_types=1);

namespace App\Support;

use FilesystemIterator;
use RuntimeException;
use Sahil\Ui\Profile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Writes the authentication implementation produced by `php sahil unda:auth`.
 *
 * The sources live in the hidden, internal `.sahil/scaffolds/auth` directory
 * (kept out of `stubs/`, which project creation and `ui:setup` delete, and
 * out of the application's public surface): `shared/` holds the model,
 * controllers, middleware, migrations, and route table; each UI profile holds
 * its templates. Every source file is read before the first is written,
 * existing files are kept unless `--force` is passed (which backs them up),
 * and every generated PHP file is syntax-checked with `php -l`.
 */
final class AuthScaffolder
{
    /** @var list<string> */
    private const MIGRATION_SUFFIXES = [
        '_create_users_table.php',
        '_create_password_reset_tokens_table.php',
        '_create_sessions_table.php',
    ];

    public function __construct(private readonly string $stubRoot)
    {
    }

    /**
     * Normalise a profile to the directory that holds its templates.
     *
     * `both` uses the Tailwind pages: the two frameworks share one component
     * vocabulary in this starter-kit, and the Tailwind set is the richer one.
     */
    public static function profileDirectory(Profile $profile): string
    {
        return $profile === Profile::Bootstrap ? 'bootstrap' : 'tailwind';
    }

    /**
     * @return array{profile: string, created: list<string>, updated: list<string>, skipped: list<string>, backups: list<string>, warnings: list<string>}
     */
    public function scaffold(string $basePath, Profile $profile, bool $force = false, bool $dryRun = false): array
    {
        $basePath = $this->projectDirectory($basePath);
        $profileDirectory = self::profileDirectory($profile);
        $extension = $this->templateExtension($basePath);
        $files = $this->stubFiles($profileDirectory, $extension);

        $report = [
            'profile' => $profileDirectory,
            'created' => [],
            'updated' => [],
            'skipped' => [],
            'backups' => [],
            'warnings' => [],
        ];

        foreach ($files as $relative => $content) {
            $path = $basePath.'/'.$relative;
            $exists = is_file($path);

            if (!$exists && $this->migrationAlreadyExists($basePath, $relative)) {
                $report['skipped'][] = $relative;
                $report['warnings'][] = sprintf(
                    '%s skipped: an equivalent migration already exists in database/migrations.',
                    $relative,
                );

                continue;
            }

            if ($exists && !$force) {
                $report['skipped'][] = $relative;

                continue;
            }

            if (!$dryRun) {
                if ($exists) {
                    $report['backups'][] = $this->backup($basePath, $relative);
                }

                $this->writeFile($path, $content);
            }

            $report[$exists ? 'updated' : 'created'][] = $relative;
        }

        if (!$dryRun) {
            $this->lint($basePath, [...$report['created'], ...$report['updated']]);

            if ($this->requireAuthRoutes($basePath)) {
                $report['updated'][] = 'routes/web.php';
            }
        }

        return $report;
    }

    /**
     * Pick the template extension used by this project.
     *
     * A project can render `.sahil.php` (the starter-kit default), `.php`, or
     * `.html` views. The first existing view decides; with no views yet the
     * starter-kit convention is used.
     */
    public function templateExtension(string $basePath): string
    {
        $root = $basePath.'/resources/views';

        if (is_dir($root)) {
            foreach ($this->relativeFiles($root) as $relative) {
                foreach (['.sahil.php', '.php', '.html'] as $candidate) {
                    if (str_ends_with($relative, $candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return '.sahil.php';
    }

    /**
     * @return array<string, string> relative path => file contents
     */
    private function stubFiles(string $profileDirectory, string $extension): array
    {
        $files = [];

        foreach (['shared', $profileDirectory] as $tree) {
            $root = $this->stubRoot.'/'.$tree;

            if (!is_dir($root)) {
                throw new RuntimeException(sprintf('missing stub directory [%s].', $root));
            }

            foreach ($this->relativeFiles($root) as $relative) {
                $content = @file_get_contents($root.'/'.$relative);

                if ($content === false) {
                    throw new RuntimeException(sprintf('unable to read stub [%s].', $relative));
                }

                $destination = $relative;
                $isView = str_starts_with($relative, 'resources/views/');

                if ($isView && str_ends_with($relative, '.sahil.php') && $extension !== '.sahil.php') {
                    $destination = substr($relative, 0, -strlen('.sahil.php')).$extension;
                }

                $files[$destination] = $content;
            }
        }

        ksort($files);

        return $files;
    }

    /**
     * True when the migration this stub would write already exists under a
     * different timestamp. The users table in particular must never be
     * declared twice.
     */
    private function migrationAlreadyExists(string $basePath, string $relative): bool
    {
        if (!str_starts_with($relative, 'database/migrations/')) {
            return false;
        }

        foreach (self::MIGRATION_SUFFIXES as $suffix) {
            if (!str_ends_with($relative, $suffix)) {
                continue;
            }

            $matches = glob($basePath.'/database/migrations/*'.$suffix) ?: [];

            return $matches !== [];
        }

        return false;
    }

    /**
     * Ensure `routes/web.php` requires the generated auth route file.
     *
     * The exact line is added once, after the strict-types declaration; an
     * existing reference to `routes/auth.php` is left untouched.
     */
    private function requireAuthRoutes(string $basePath): bool
    {
        $path = $basePath.'/routes/web.php';

        if (!is_file($path)) {
            return false;
        }

        $contents = (string) file_get_contents($path);

        if (str_contains($contents, "auth.php")) {
            return false;
        }

        $line = "require __DIR__.'/auth.php';\n";

        if (preg_match('/^declare\\(strict_types=1\\);\\R/m', $contents) === 1) {
            $contents = (string) preg_replace(
                '/^declare\\(strict_types=1\\);\\R/m',
                "declare(strict_types=1);\n\n".$line,
                $contents,
                1,
            );
        } else {
            $contents = $line.$contents;
        }

        $this->writeFile($path, $contents);

        return true;
    }

    /**
     * Syntax-check every generated PHP file. A parse error aborts the command
     * with the file name and the parser's own message.
     *
     * @param list<string> $relativePaths
     */
    private function lint(string $basePath, array $relativePaths): void
    {
        foreach ($relativePaths as $relative) {
            if (!str_ends_with($relative, '.php')) {
                continue;
            }

            $path = $basePath.'/'.$relative;
            $command = [PHP_BINARY, '-l', $path];
            $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $pipes = [];
            $process = proc_open($command, $descriptor, $pipes);

            if (!is_resource($process)) {
                throw new RuntimeException(sprintf('unable to run php -l for [%s].', $relative));
            }

            $stdout = stream_get_contents($pipes[1]) ?: '';
            $stderr = stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);

            if (proc_close($process) !== 0) {
                throw new RuntimeException(sprintf(
                    "generated file [%s] failed php -l:\n%s",
                    $relative,
                    trim($stdout.$stderr),
                ));
            }
        }
    }

    /**
     * @return list<string>
     */
    private function relativeFiles(string $root): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        }

        sort($files);

        return $files;
    }

    private function projectDirectory(string $path): string
    {
        if (!is_dir($path)) {
            throw new RuntimeException(sprintf('project directory [%s] does not exist.', $path));
        }

        $resolved = realpath($path);

        if ($resolved === false) {
            throw new RuntimeException(sprintf('unable to resolve project directory [%s].', $path));
        }

        if (!is_writable($resolved)) {
            throw new RuntimeException(sprintf('project directory [%s] is not writable.', $resolved));
        }

        return $resolved;
    }

    private function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('unable to create directory [%s].', $directory));
        }

        $temporary = $path.'.tmp-'.bin2hex(random_bytes(4));

        if (file_put_contents($temporary, $content) === false) {
            @unlink($temporary);

            throw new RuntimeException(sprintf('unable to write [%s].', $path));
        }

        if (!rename($temporary, $path)) {
            @unlink($temporary);

            throw new RuntimeException(sprintf('unable to replace [%s].', $path));
        }
    }

    private function backup(string $basePath, string $relative): string
    {
        $backupRoot = sprintf(
            'storage/framework/auth-backups/%s-%s',
            gmdate('Ymd-His'),
            bin2hex(random_bytes(3)),
        );
        $destination = $basePath.'/'.$backupRoot.'/'.$relative;

        if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0o775, true) && !is_dir(dirname($destination))) {
            throw new RuntimeException(sprintf('unable to create backup directory [%s].', dirname($destination)));
        }

        if (!copy($basePath.'/'.$relative, $destination)) {
            throw new RuntimeException(sprintf('unable to back up [%s].', $relative));
        }

        return $backupRoot.'/'.$relative;
    }
}
