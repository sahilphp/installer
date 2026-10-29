<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Service;

use Symfony\Component\Process\Process;

/**
 * Inspects the environment the installer runs in.
 *
 * Every check is self-contained: an unexpected failure (for example a binary
 * that cannot be spawned) is reported as a failed check instead of bubbling up
 * as an exception. Checks never read or print secrets such as Composer
 * credentials or repository tokens.
 *
 * @phpstan-type Requirement array{name: string, status: string, detail: string}
 */
class RequirementsChecker
{
    public const OK = 'ok';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    public const MINIMUM_PHP_VERSION = '8.4.0';

    /**
     * Extensions a generated project needs at runtime.
     *
     * @var list<string>
     */
    private const REQUIRED_EXTENSIONS = ['curl', 'fileinfo', 'mbstring', 'openssl'];

    /**
     * @var list<string>
     */
    private array $extensions;

    private readonly string $minimumPhpVersion;

    /**
     * @param list<string>|null $extensions       Extensions to check; defaults to the ones generated projects need
     * @param string            $minimumPhpVersion Lowest PHP version accepted by SahilPHP
     */
    public function __construct(?array $extensions = null, string $minimumPhpVersion = self::MINIMUM_PHP_VERSION)
    {
        $this->extensions = $extensions ?? self::REQUIRED_EXTENSIONS;
        $this->minimumPhpVersion = $minimumPhpVersion;
    }

    /**
     * @return list<array{name: string, status: string, detail: string}>
     */
    public function check(): array
    {
        $results = [
            $this->guard('php', fn (): array => $this->checkPhpVersion()),
        ];

        foreach ($this->extensions as $extension) {
            $results[] = $this->guard(
                'ext-'.$extension,
                fn (): array => $this->checkExtension($extension)
            );
        }

        $results[] = $this->guard('composer', fn (): array => $this->checkComposer());
        $results[] = $this->guard('node', fn (): array => $this->checkNode());
        $results[] = $this->guard('git', fn (): array => $this->checkGit());
        $results[] = $this->guard('working-directory', fn (): array => $this->checkWorkingDirectory());

        return $results;
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkPhpVersion(): array
    {
        if (version_compare(PHP_VERSION, $this->minimumPhpVersion, '<')) {
            return [
                'name' => 'php',
                'status' => self::ERROR,
                'detail' => sprintf(
                    'PHP %s is too old - SahilPHP requires PHP %s or newer. Upgrade PHP, then run "sahilphp doctor" again.',
                    PHP_VERSION,
                    $this->minimumPhpVersion
                ),
            ];
        }

        return [
            'name' => 'php',
            'status' => self::OK,
            'detail' => sprintf(
                'PHP %s is supported (SahilPHP requires PHP %s or newer).',
                PHP_VERSION,
                $this->minimumPhpVersion
            ),
        ];
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkExtension(string $extension): array
    {
        $name = 'ext-'.$extension;

        if (\extension_loaded($extension)) {
            return [
                'name' => $name,
                'status' => self::OK,
                'detail' => sprintf('The PHP extension "%s" is available.', $extension),
            ];
        }

        $version = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        return [
            'name' => $name,
            'status' => self::ERROR,
            'detail' => sprintf(
                'The PHP extension "%s" is missing - install it (for example "sudo apt install php%s-%s" or "sudo dnf install php-%s"), then run "sahilphp doctor" again.',
                $extension,
                $version,
                $extension,
                $extension
            ),
        ];
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkComposer(): array
    {
        $version = $this->firstLineOf(['composer', '--version']);

        if (null === $version) {
            return [
                'name' => 'composer',
                'status' => self::ERROR,
                'detail' => 'Composer was not found on PATH - install it from https://getcomposer.org/download/ so "sahilphp new" can create projects.',
            ];
        }

        return [
            'name' => 'composer',
            'status' => self::OK,
            'detail' => sprintf('%s is available.', $version),
        ];
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkNode(): array
    {
        $node = $this->firstLineOf(['node', '--version']);

        if (null === $node) {
            return [
                'name' => 'node',
                'status' => self::WARNING,
                'detail' => 'Node.js was not found on PATH - frontend assets will not be built automatically. Install Node.js 20 or newer from https://nodejs.org, or run "npm install && npm run build" inside the project later.',
            ];
        }

        $node = ltrim($node, 'v');

        $npm = $this->firstLineOf(['npm', '--version']);

        if (null === $npm) {
            return [
                'name' => 'node',
                'status' => self::WARNING,
                'detail' => sprintf(
                    'Node.js %s is available but npm was not found on PATH - install npm (it ships with Node.js), or run "npm install" inside the project later.',
                    $node
                ),
            ];
        }

        return [
            'name' => 'node',
            'status' => self::OK,
            'detail' => sprintf('Node.js %s and npm %s are available.', $node, $npm),
        ];
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkGit(): array
    {
        $version = $this->firstLineOf(['git', '--version']);

        if (null === $version) {
            return [
                'name' => 'git',
                'status' => self::WARNING,
                'detail' => 'git was not found on PATH - creating a project from a released package still works, but installing from a VCS repository will fail. Install git if you need it.',
            ];
        }

        return [
            'name' => 'git',
            'status' => self::OK,
            'detail' => sprintf('%s is available.', $version),
        ];
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkWorkingDirectory(): array
    {
        $directory = getcwd();

        if (false === $directory) {
            return [
                'name' => 'working-directory',
                'status' => self::ERROR,
                'detail' => 'The current working directory could not be determined - change into the directory where the project should be created and run "sahilphp doctor" again.',
            ];
        }

        if (!is_writable($directory)) {
            return [
                'name' => 'working-directory',
                'status' => self::ERROR,
                'detail' => sprintf(
                    'The current directory "%s" is not writable - change into a writable directory (for example your home directory) before creating a project.',
                    $directory
                ),
            ];
        }

        return [
            'name' => 'working-directory',
            'status' => self::OK,
            'detail' => sprintf('The current directory "%s" is writable.', $directory),
        ];
    }

    /**
     * Runs a check and turns an unexpected failure into a failed result.
     *
     * @param callable(): array{name: string, status: string, detail: string} $check
     *
     * @return array{name: string, status: string, detail: string}
     */
    private function guard(string $name, callable $check): array
    {
        try {
            return $check();
        } catch (\Throwable $exception) {
            return [
                'name' => $name,
                'status' => self::ERROR,
                'detail' => sprintf('The "%s" check could not be completed: %s', $name, $exception->getMessage()),
            ];
        }
    }

    /**
     * Runs a harmless version command and returns its first output line.
     *
     * @param list<string> $command
     */
    private function firstLineOf(array $command): ?string
    {
        try {
            $process = new Process($command);
            $process->setTimeout(10.0);
            $process->run();
        } catch (\Throwable) {
            return null;
        }

        if (!$process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());

        if ('' === $output) {
            $output = trim($process->getErrorOutput());
        }

        $line = strtok($output, "\n");

        if (false === $line || '' === trim($line)) {
            return null;
        }

        return trim($line);
    }
}
