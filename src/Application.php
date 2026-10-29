<?php

declare(strict_types=1);

namespace SahilPHP\Installer;

use Composer\InstalledVersions;
use SahilPHP\Installer\Command\DoctorCommand;
use SahilPHP\Installer\Command\NewCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Application extends BaseApplication
{
    public const NAME = 'SahilPHP Installer';
    private const PACKAGE = 'sahilphp/installer';

    public function __construct()
    {
        parent::__construct(self::NAME, self::resolveVersion());

        $this->addCommands([
            new NewCommand(),
            new DoctorCommand(),
        ]);
    }

    /**
     * "sahilphp --help" lists the available commands instead of the help of the
     * internal "list" command, so the help output reflects what is registered.
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        if (null === $input->getFirstArgument() && true === $input->hasParameterOption(['--help', '-h'], true)) {
            $input = new ArrayInput(['command' => 'list']);
        }

        return parent::doRun($input, $output);
    }

    private static function resolveVersion(): string
    {
        if (class_exists(InstalledVersions::class)) {
            try {
                if (InstalledVersions::isInstalled(self::PACKAGE)) {
                    $version = InstalledVersions::getPrettyVersion(self::PACKAGE);
                    if ($version !== null && $version !== '') {
                        return ltrim($version, 'v');
                    }
                }
                $root = InstalledVersions::getRootPackage();
                if ($root['name'] === self::PACKAGE && $root['pretty_version'] !== '') {
                    return ltrim($root['pretty_version'], 'v');
                }
            } catch (\Throwable) {
            }
        }

        return 'dev';
    }
}
