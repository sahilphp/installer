<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Service;

use SahilPHP\Installer\Ui\UiChoice;
use SahilPHP\Installer\Ui\UiKit;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Handles the creation of new SahilPHP projects.
 */
class ProjectCreator
{
    private readonly Filesystem $filesystem;
    private readonly ProjectNameValidator $nameValidator;
    private readonly NodeRunner $nodeRunner;
    private readonly UiConfigGenerator $uiConfig;

    public function __construct(
        ?Filesystem $filesystem = null,
        ?ProjectNameValidator $nameValidator = null,
        ?NodeRunner $nodeRunner = null,
        ?UiConfigGenerator $uiConfig = null
    ) {
        $this->filesystem = $filesystem ?? new Filesystem();
        $this->nameValidator = $nameValidator ?? new ProjectNameValidator();
        $this->nodeRunner = $nodeRunner ?? new NodeRunner();
        $this->uiConfig = $uiConfig ?? new UiConfigGenerator();
    }

    /**
     * Create a new SahilPHP project.
     *
     * @param string $name Project name
     * @param string $directory Target directory (defaults to current directory + name)
     * @param UiKit $uiKit UI kit choice
     * @param UiChoice $css CSS framework choice
     * @param SymfonyStyle $io Console I/O for interaction
     * @param bool $installDependencies Whether to install Composer and npm dependencies
     * @return bool True on success, false on failure
     */
    public function create(
        string $name,
        string $directory,
        UiKit $uiKit,
        UiChoice $css,
        SymfonyStyle $io,
        bool $installDependencies = true
    ): bool {
        // Validate project name
        if (!$this->nameValidator->isValidOrNull($name)) {
            $io->error('Invalid project name: ' . implode(', ', $this->nameValidator->getErrors($name)));
            return false;
        }

        // Resolve target directory
        if ($directory === '' || $directory === '.') {
            $directory = getcwd() . '/' . $name;
        }

        // Check if directory already exists
        if ($this->filesystem->exists($directory)) {
            $io->error("Directory '{$directory}' already exists. Please choose a different name or remove the existing directory.");
            return false;
        }

        $io->writeln(sprintf('<info>Creating SahilPHP project "%s" in %s...</info>', $name, $directory));

        try {
            // Create project directory
            $this->filesystem->mkdir($directory);

            // Copy starter-kit files
            $io->writeln('<comment>Copying starter-kit files...</comment>');
            if (!$this->copyStarterKit($directory, $io)) {
                $io->error('Failed to copy starter-kit files.');
                Cleaner::remove($directory);
                return false;
            }

            // Apply the chosen UI kit and CSS framework before dependencies
            // are installed. `ui:setup` needs no vendor tree, so this also
            // works for --no-deps projects.
            $io->writeln(sprintf(
                '<comment>Configuring UI kit [%s] and CSS [%s]...</comment>',
                $uiKit->value,
                $css->value,
            ));
            if (!$this->configureUi($directory, $uiKit, $css, $io)) {
                $io->error('Failed to configure UI kit and CSS framework.');
                Cleaner::remove($directory);
                return false;
            }

            // Update composer.json with project name
            $this->updateComposerJson($directory, $name);

            // Require only the UI kit chosen by the user; the starter-kit's
            // default `sahilphp/firefiber` dependency is removed unless it was
            // chosen, so generated projects never ship unchosen UI libraries.
            $this->syncUiDependencies($directory, $uiKit);

            if ($installDependencies) {
                // Install Composer dependencies
                $io->writeln('<comment>Installing Composer dependencies...</comment>');
                if (!$this->installComposerDependencies($directory, $io)) {
                    $io->writeln('<comment>Removing the partial project directory.</comment>');
                    Cleaner::remove($directory);
                    return false;
                }

                // Install npm dependencies and build
                $io->writeln('<comment>Installing npm dependencies and building assets...</comment>');
                $nodeResult = $this->nodeRunner->installAndBuild($directory, $io);
                if (!$nodeResult['success']) {
                    $io->warning($nodeResult['message']);
                }
            }

            // Run post-create-project scripts
            $io->writeln('<comment>Running post-create-project scripts...</comment>');
            $this->runPostCreateScripts($directory, $io);

            $io->success(sprintf('Project "%s" created successfully!', $name));
            $io->writeln('');
            $io->writeln('<info>Next steps:</info>');

            foreach (self::nextSteps($name, $uiKit, $css) as $line) {
                $io->writeln($line === '' ? '' : '  ' . $line);
            }

            $io->writeln('');

            return true;
        } catch (\Throwable $e) {
            $io->error(sprintf('Project creation failed: %s', $e->getMessage()));
            if ($this->filesystem->exists($directory)) {
                $io->writeln('<comment>Cleaning up partial installation...</comment>');
                Cleaner::remove($directory);
            }
            return false;
        }
    }

    /**
     * Commands a user runs after `sahilphp new`.
     *
     * The generated project ships `bin/sahil start`, so nobody is told to
     * assemble a raw `php -S … -t public public/index.php` command by hand.
     *
     * @return list<string>
     */
    public static function nextSteps(string $name, UiKit $uiKit, UiChoice $css): array
    {
        return [
            sprintf('cd %s', $name),
            'php sahil start                 (http://127.0.0.1:8000)',
            'composer start                  (the same server through Composer)',
            'php sahil start --port=8088     (use another port)',
            '',
            sprintf('UI kit: %s (%s)', $uiKit->shortLabel(), $uiKit->value),
            sprintf('CSS framework: %s (%s)', $css->label, $css->value),
            'http://127.0.0.1:8000/          welcome page',
            'http://127.0.0.1:8000/ui-kit    component gallery',
            'http://127.0.0.1:8000/health    health check',
            '',
            'Generate the complete authentication implementation with: php sahil make:auth',
            'Then open:',
            'http://127.0.0.1:8000/login',
            'http://127.0.0.1:8000/register',
            'http://127.0.0.1:8000/dashboard',
        ];
    }

    /**
     * Copy starter-kit files to the project directory.
     */
    private function copyStarterKit(string $directory, SymfonyStyle $io): bool
    {
        $starterKitPath = $this->findStarterKitPath();

        if ($starterKitPath === null) {
            // Use composer create-project to download the starter-kit
            $io->writeln('<comment>Downloading starter-kit from Packagist...</comment>');

            // Dependencies are installed after the UI profile is applied, so the
            // package's post-create-project-cmd must not run here: it invokes
            // bin/sahil, which needs vendor/autoload.php.
            $process = new Process([
                'composer',
                'create-project',
                'sahilphp/sahilphp',
                $directory,
                '--no-install',
                '--no-scripts',
                '--prefer-dist',
                '--no-interaction'
            ]);
            $process->setTimeout(600);

            $process->run(function ($type, $buffer) use ($io) {
                $io->write($buffer, false);
            });

            if (!$process->isSuccessful()) {
                return false;
            }

            // Remove unnecessary files
            $filesToRemove = [
                $directory . '/.git',
                $directory . '/composer.lock',
                $directory . '/node_modules',
                $directory . '/dist',
            ];

            foreach ($filesToRemove as $file) {
                if ($this->filesystem->exists($file)) {
                    $this->filesystem->remove($file);
                }
            }

            return true;
        }

        // Copy from local path
        $this->filesystem->mirror($starterKitPath, $directory, null, [
            'override' => true,
            'delete' => false,
        ]);

        // Remove unnecessary files
        $filesToRemove = [
            $directory . '/.git',
            $directory . '/composer.lock',
            $directory . '/node_modules',
            $directory . '/dist',
        ];

        foreach ($filesToRemove as $file) {
            if ($this->filesystem->exists($file)) {
                $this->filesystem->remove($file);
            }
        }

        return true;
    }

    /**
     * Find the starter-kit path.
     * Checks multiple locations: local development, vendor, or uses Composer to download.
     */
    private function findStarterKitPath(): ?string
    {
        // Check if starter-kit is in the parent directory (local development)
        $localPath = dirname(__DIR__, 3) . '/starter-kit';
        if ($this->filesystem->exists($localPath . '/composer.json')) {
            return $localPath;
        }

        // Check if starter-kit is in vendor (when installer is installed globally)
        $vendorPath = dirname(__DIR__, 5) . '/sahilphp/starter-kit';
        if ($this->filesystem->exists($vendorPath . '/composer.json')) {
            return $vendorPath;
        }

        return null;
    }

    /**
     * Apply the selected UI kit and CSS framework through the starter kit's
     * own `ui:setup` command.
     *
     * The two choices travel as environment variables as well as flags, so
     * the starter kit never asks the user a second time. Only the chosen
     * stub trees are copied, and `ui:setup` deletes `stubs/` afterwards.
     */
    private function configureUi(
        string $directory,
        UiKit $uiKit,
        UiChoice $css,
        SymfonyStyle $io
    ): bool {
        if (!$this->filesystem->exists($directory . '/bin/sahil')) {
            $io->error('bin/sahil is missing from the starter-kit; UI setup cannot run.');

            return false;
        }

        $process = new Process(
            [
                PHP_BINARY,
                'bin/sahil',
                'ui:setup',
                '--ui-kit=' . $uiKit->value,
                '--css=' . $css->value,
            ],
            $directory,
            [
                'SAHILPHP_UI_KIT' => $uiKit->value,
                'SAHILPHP_CSS' => $css->value,
                'SAHILPHP_INSTALLER' => '1',
            ],
        );
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer, false);
        });

        if (!$process->isSuccessful()) {
            $this->reportProcessFailure($io, 'php bin/sahil ui:setup', $process);

            return false;
        }

        // `ui:setup` removes the stub tree itself; keep this as a safety net
        // for an interrupted run.
        if ($this->filesystem->exists($directory . '/stubs')) {
            $this->filesystem->remove($directory . '/stubs');
        }

        return true;
    }

    /**
     * Make the generated project require exactly the selected UI library.
     *
     * The starter-kit ships `sahilphp/firefiber` as its default UI
     * dependency. Project creation must install only the UI kit chosen
     * by the user, so every UI integration package is dropped from the
     * generated `composer.json` and only the selected one is required
     * (none when "none" is chosen). The change happens before
     * `composer install`, so the lock file never contains an unchosen
     * UI package.
     *
     * @var list<string>
     */
    private const UI_PACKAGES = [
        'sahilphp/firefiber',
        'sahilphp/react',
        'sahilphp/vue',
        'sahilphp/svelte',
    ];

    private function syncUiDependencies(string $directory, UiKit $uiKit): void
    {
        $composerJsonPath = $directory . '/composer.json';

        if (!$this->filesystem->exists($composerJsonPath)) {
            return;
        }

        $content = file_get_contents($composerJsonPath);
        if ($content === false) {
            return;
        }

        $composerJson = json_decode($content, true);
        if (!is_array($composerJson)) {
            return;
        }

        $require = $composerJson['require'] ?? [];
        if (!is_array($require)) {
            $require = [];
        }

        foreach (self::UI_PACKAGES as $package) {
            unset($require[$package]);
        }

        if ($uiKit !== UiKit::None) {
            $require['sahilphp/'.$uiKit->value] = '^1.0';
        }

        // The starter-kit ships a path repository to the colocated `firefiber`
        // checkout (monorepo local development). A generated project lives
        // outside that layout, so the entry would point at nothing and break
        // `composer install`; UI packages resolve from Packagist (or from
        // repositories the operator configures) instead.
        unset($composerJson['repositories']);

        ksort($require);
        $composerJson['require'] = $require;

        if (file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            throw new \RuntimeException('Unable to update '.$composerJsonPath.' with the selected UI package.');
        }
    }

    /**
     * Update composer.json with the project name.
     */
    private function updateComposerJson(string $directory, string $name): void
    {
        $composerJsonPath = $directory . '/composer.json';

        if (!$this->filesystem->exists($composerJsonPath)) {
            return;
        }

        $content = file_get_contents($composerJsonPath);
        if ($content === false) {
            return;
        }

        $composerJson = json_decode($content, true);
        if (!is_array($composerJson)) {
            return;
        }

        $composerJson['name'] = $this->composerPackageName($name, $composerJson['name'] ?? null);

        file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Build a schema-valid Composer package name for the new project.
     *
     * Composer requires "vendor/package", so the starter-kit's vendor segment
     * is kept and only the package segment comes from the project name.
     */
    private function composerPackageName(string $name, mixed $currentName): string
    {
        $vendor = 'sahilphp';

        if (is_string($currentName) && str_contains($currentName, '/')) {
            $candidate = strtolower((string) strstr($currentName, '/', true));

            if (preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*$/', $candidate) === 1) {
                $vendor = $candidate;
            }
        }

        $package = trim(strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $name)), '-');

        return $vendor . '/' . ($package === '' ? 'app' : $package);
    }

    /**
     * Install Composer dependencies.
     */
    private function installComposerDependencies(string $directory, SymfonyStyle $io): bool
    {
        $process = new Process(['composer', 'install', '--no-interaction', '--prefer-dist']);
        $process->setWorkingDirectory($directory);
        $process->setTimeout(600);

        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer, false);
        });

        if (!$process->isSuccessful()) {
            $this->reportProcessFailure($io, 'composer install', $process);

            return false;
        }

        return true;
    }

    /**
     * Run post-create-project scripts.
     */
    private function runPostCreateScripts(string $directory, SymfonyStyle $io): void
    {
        // Copy .env.example to .env if it doesn't exist
        $envExample = $directory . '/.env.example';
        $envFile = $directory . '/.env';

        if ($this->filesystem->exists($envExample) && !$this->filesystem->exists($envFile)) {
            $this->filesystem->copy($envExample, $envFile);
            $io->writeln('<comment>Created .env file from .env.example</comment>');
        }

        if (!$this->filesystem->exists($directory . '/vendor/autoload.php')) {
            $io->warning('Composer dependencies are not installed; run "composer install", then "php bin/sahil key:generate" and "php bin/sahil storage:prepare".');

            return;
        }

        if (!$this->filesystem->exists($directory . '/bin/sahil')) {
            $io->warning('bin/sahil is missing; the application key and storage directories were not prepared.');

            return;
        }

        $steps = [
            'key:generate' => 'Application key generated',
            'storage:prepare' => 'Storage prepared',
        ];

        foreach ($steps as $command => $success) {
            $process = new Process(['php', 'bin/sahil', $command]);
            $process->setWorkingDirectory($directory);
            $process->run();

            if ($process->isSuccessful()) {
                $io->writeln('<comment>' . $success . '</comment>');

                continue;
            }

            $this->reportProcessFailure($io, 'php bin/sahil ' . $command, $process);
        }
    }

    /**
     * Report why an external command failed.
     *
     * The captured stderr is the only place the cause is recorded, so it is
     * printed instead of leaving a generic failure line or no line at all.
     */
    private function reportProcessFailure(SymfonyStyle $io, string $command, Process $process): void
    {
        $exitCode = $process->getExitCode();

        $io->error(sprintf(
            '%s failed%s.',
            $command,
            $exitCode === null ? '' : sprintf(' (exit code %d)', $exitCode)
        ));

        $details = trim($process->getErrorOutput());

        if ($details === '') {
            $details = trim($process->getOutput());
        }

        if ($details === '') {
            return;
        }

        foreach (array_slice(preg_split('/\R/', $details) ?: [], -15) as $line) {
            $io->writeln('  ' . $line);
        }
    }
}
