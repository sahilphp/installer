<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class NewCommand extends Command
{
    public function __construct()
    {
        parent::__construct('new');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new SahilPHP project')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command creates a new SahilPHP project
                in the current directory. Run <info>sahilphp doctor</info> first to make
                sure your environment has everything the project needs.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Creating a new SahilPHP project...");

        return self::SUCCESS;
    }
}
