<?php

declare(strict_types=1);

namespace SahilPHP\Installer;

use Symfony\Component\Console\Application as ConsoleApplication;

final class Application extends ConsoleApplication
{
    public function __construct()
    {
        parent::__construct(
            'SahilPHP Installer',
            \InstalledVersions::getVersion(self::class) ?? 'dev'
        );

        $this->addCommands([
            new Command\NewCommand(),
            new Command\DoctorCommand(),
        ]);

        $this->setDefaultCommand('new', true);
    }
}