<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Command;

use SahilPHP\Installer\Service\ProjectCreator;
use SahilPHP\Installer\Service\ProjectNameValidator;
use SahilPHP\Installer\Ui\UiChoice;
use SahilPHP\Installer\Ui\UiKit;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

final class NewCommand extends Command
{
    private const UI_PACKAGES = [
        'sahilphp/firefiber',
        'sahilphp/react',
        'sahilphp/vue',
        'sahilphp/svelte',
    ];

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
                'ui-kit',
                null,
                InputOption::VALUE_REQUIRED,
                'UI kit to use (firefiber, react, vue, svelte or none)',
                null
            )
            ->addOption(
                'css',
                null,
                InputOption::VALUE_REQUIRED,
                'CSS framework to use (tailwind or bootstrap)',
                null
            )
            ->addOption(
                'ui',
                null,
                InputOption::VALUE_REQUIRED,
                'Alias for --ui-kit (UI integration: firefiber, react, vue, svelte or none)',
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

                It always asks two questions, in order, before writing any file:

                  1. Which UI kit: Firefiber, React, Vue, Svelte or None
                  2. Which CSS framework: Tailwind CSS v4 or Bootstrap 5

                <info>Interactive mode:</info>
                  sahilphp new
                  sahilphp new my-app

                <info>Non-interactive mode (--css is required):</info>
                  sahilphp new my-app --ui=react --css=tailwind
                  sahilphp new my-app --ui-kit=vue --css=bootstrap
                  sahilphp new my-app --ui=none --css=tailwind --no-deps

                Run <info>sahilphp doctor</info> first to make sure your environment
                has everything the project needs.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $directory = $input->getOption('dir') ?? '';
        $installDeps = !$input->getOption('no-deps');
        $interactive = $input->isInteractive();

        if (is_string($name) && $this->looksLikeUiPackage($name)) {
            $io->error(sprintf(
                "\"%s\" looks like a SahilPHP UI package, not a project name.\nDid you mean: sahilphp new my-app ?",
                trim($name),
            ));

            return self::FAILURE;
        }

        // Interactive mode: prompt for the project name.
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
        if (!is_string($name) || !$this->nameValidator->isValid($name)) {
            $io->error('Invalid project name: ' . implode(', ', $this->nameValidator->getErrors((string) $name)));
            return self::FAILURE;
        }

        $uiKit = $this->resolveUiKit($input, $io, $interactive);

        if ($uiKit === null) {
            return self::FAILURE;
        }

        $css = $this->resolveCss($input, $io, $interactive);

        if ($css === null) {
            return self::FAILURE;
        }

        // Both answers are known now; nothing has been written yet.
        $success = $this->creator->create(
            $name,
            $directory,
            $uiKit,
            $css,
            $io,
            $installDeps
        );

        return $success ? self::SUCCESS : self::FAILURE;
    }

    private function looksLikeUiPackage(string $name): bool
    {
        return in_array(strtolower(trim($name)), self::UI_PACKAGES, true);
    }

    private function resolveUiKit(InputInterface $input, SymfonyStyle $io, bool $interactive): ?UiKit
    {
        $option = $input->getOption('ui-kit') ?? $input->getOption('ui');

        if ($option !== null) {
            $uiKit = UiKit::fromName((string) $option);

            if ($uiKit === null) {
                $io->error(sprintf(
                    'Invalid UI kit "%s". Valid options are: %s',
                    (string) $option,
                    implode(', ', UiKit::values()),
                ));

                return null;
            }

            return $uiKit;
        }

        if (!$interactive) {
            $io->note('No UI kit selected; using "Sahil Engine" (native SahilPHP template engine).');

            return UiKit::None;
        }

        $choices = UiKit::numberedChoices();
        $question = new ChoiceQuestion('Choose your UI kit:', $choices, null);
        $question->setErrorMessage('"%s" is not a valid UI kit. Enter a number (1-5) or a name.');
        $question->setValidator(static function (mixed $answer) use ($choices): string {
            $value = trim((string) $answer);

            if (ctype_digit($value) && isset($choices[(int) $value])) {
                return $value;
            }

            $uiKit = UiKit::fromName($value);

            if ($uiKit !== null) {
                foreach ($choices as $key => $label) {
                    if (str_starts_with($label, $uiKit->shortLabel())) {
                        return (string) $key;
                    }
                }
            }

            throw new \RuntimeException(sprintf(
                '"%s" is not a valid UI kit. Enter a number (1-5) or a name.',
                $value,
            ));
        });

        $selected = (string) $io->askQuestion($question);
        $uiKit = UiKit::cases()[(int) $selected - 1] ?? null;

        if ($uiKit === null) {
            $io->error('Invalid UI kit selection.');

            return null;
        }

        return $uiKit;
    }

    private function resolveCss(InputInterface $input, SymfonyStyle $io, bool $interactive): ?UiChoice
    {
        $option = $input->getOption('css');

        if ($option !== null) {
            $css = UiChoice::fromString((string) $option);

            if ($css === null) {
                $io->error(sprintf(
                    'Invalid CSS framework "%s". Valid options are: tailwind, bootstrap',
                    (string) $option,
                ));

                return null;
            }

            return $css;
        }

        if (!$interactive) {
            $io->error('CSS framework is required. Use --css=tailwind or --css=bootstrap.');

            return null;
        }

        $choices = UiChoice::numberedChoices();
        $question = new ChoiceQuestion('Choose your CSS framework:', $choices, null);
        $question->setErrorMessage('"%s" is not a valid CSS framework. Enter 1 for Tailwind CSS v4 or 2 for Bootstrap 5.');
        $question->setValidator(static function (mixed $answer) use ($choices): string {
            $value = trim((string) $answer);

            if (ctype_digit($value) && isset($choices[(int) $value])) {
                return $value;
            }

            $css = UiChoice::fromString($value);

            if ($css !== null) {
                foreach ($choices as $key => $label) {
                    if (str_contains($label, $css->value === 'tailwind' ? 'Tailwind' : 'Bootstrap')) {
                        return (string) $key;
                    }
                }
            }

            throw new \RuntimeException(sprintf(
                '"%s" is not a valid CSS framework. Enter 1 for Tailwind CSS v4 or 2 for Bootstrap 5.',
                $value,
            ));
        });

        $selected = (string) $io->askQuestion($question);

        return match ($selected) {
            '1' => UiChoice::tailwind(),
            '2' => UiChoice::bootstrap(),
            default => null,
        };
    }
}
