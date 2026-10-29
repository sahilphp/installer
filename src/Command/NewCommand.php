<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Command;

use SahilPHP\Installer\Service\ProjectCreator;
use SahilPHP\Installer\Service\ProjectNameValidator;
use SahilPHP\Installer\Ui\UiChoice;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class NewCommand extends Command
{
    private readonly ProjectCreator $creator;
    private readonly ProjectNameValidator $nameValidator;

    public function __construct(
        ?ProjectCreator $creator = null,
        ?ProjectNameValidator $nameValidator = null
    ) {
        $this->creator = $creator ?? new ProjectCreator();
        $this->nameValidator = $nameValidator ?? new ProjectNameValidator();

        parent::__construct('new');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new SahilPHP project')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'The name of the project (e.g., my-app)'
            )
            ->addOption(
                'ui',
                null,
                InputOption::VALUE_REQUIRED,
                'UI framework to use (tailwind or bootstrap)',
                null
            )
            ->addOption(
                'dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Target directory (defaults to current directory + project name)',
                null
            )
            ->addOption(
                'no-deps',
                null,
                InputOption::VALUE_NONE,
                'Skip installing Composer and npm dependencies'
            )
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command creates a new SahilPHP project.

                <info>Interactive mode:</info>
                  sahilphp new

                <info>Direct creation:</info>
                  sahilphp new my-app
                  sahilphp new my-app --ui=tailwind
                  sahilphp new my-app --ui=bootstrap

                Run <info>sahilphp doctor</info> first to make sure your environment
                has everything the project needs.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $uiOption = $input->getOption('ui');
        $directory = $input->getOption('dir') ?? '';
        $installDeps = !$input->getOption('no-deps');

        // Interactive mode: prompt for missing values
        if ($name === null) {
            $io->title('SahilPHP Project Creator');

            $name = $io->ask('Project name', null, function ($answer) {
                if (!$this->nameValidator->isValid($answer)) {
                    throw new \RuntimeException(implode(', ', $this->nameValidator->getErrors($answer)));
                }
                return $answer;
            });
        }

        // Validate project name
        if (!$this->nameValidator->isValid($name)) {
            $io->error('Invalid project name: ' . implode(', ', $this->nameValidator->getErrors($name)));
            return self::FAILURE;
        }

        // Determine UI framework
        $ui = null;
        if ($uiOption !== null) {
            $ui = UiChoice::fromString($uiOption);
            if ($ui === null) {
                $io->error(sprintf('Invalid UI framework "%s". Valid options are: tailwind, bootstrap', $uiOption));
                return self::FAILURE;
            }
        } else {
            // Interactive prompt for UI
            $uiChoices = array_map(fn ($choice) => $choice->label, UiChoice::all());
            $selectedLabel = $io->choice('Which UI framework would you like to use?', $uiChoices, 0);
            $index = array_search($selectedLabel, $uiChoices, true);
            if ($index === false) {
                $io->error('Invalid UI selection');
                return self::FAILURE;
            }
            $allChoices = UiChoice::all();
            $ui = $allChoices[$index] ?? null;
            if ($ui === null) {
                $io->error('Invalid UI selection');
                return self::FAILURE;
            }
        }

        // Create the project
        $success = $this->creator->create(
            $name,
            $directory,
            $ui,
            $io,
            $installDeps
        );

        return $success ? self::SUCCESS : self::FAILURE;
    }
}
