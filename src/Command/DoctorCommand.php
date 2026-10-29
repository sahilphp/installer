<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Command;

use SahilPHP\Installer\Service\RequirementsChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DoctorCommand extends Command
{
    private readonly RequirementsChecker $checker;

    public function __construct(?RequirementsChecker $checker = null)
    {
        $this->checker = $checker ?? new RequirementsChecker();

        parent::__construct('doctor');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Check that this machine can run SahilPHP and create projects')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command inspects the PHP runtime and the
                command-line tools used by <info>sahilphp new</info>:

                  * the PHP version and the required PHP extensions
                  * Composer, Node.js/npm and git
                  * whether the current directory is writable

                Each check prints one actionable line. The command exits with status 1
                when a required check fails and with status 0 otherwise; missing
                optional tools are reported as warnings.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>SahilPHP Installer diagnostics</info>');
        $output->writeln('');

        try {
            $results = $this->checker->check();
        } catch (\Throwable $exception) {
            $output->writeln(sprintf(
                '<error>Diagnostics could not run: %s</error>',
                $exception->getMessage()
            ));

            return self::FAILURE;
        }

        $failed = 0;
        $warnings = 0;

        foreach ($results as $result) {
            $output->writeln($this->format($result));

            if (RequirementsChecker::ERROR === $result['status']) {
                ++$failed;
            } elseif (RequirementsChecker::WARNING === $result['status']) {
                ++$warnings;
            }
        }

        $output->writeln('');

        if ($failed > 0) {
            $output->writeln(sprintf(
                '<error>%d required check%s failed. Fix the item%s marked [FAIL] above, then run "sahilphp doctor" again.</error>',
                $failed,
                1 === $failed ? '' : 's',
                1 === $failed ? '' : 's'
            ));

            return self::FAILURE;
        }

        if ($warnings > 0) {
            $output->writeln(sprintf(
                '<comment>%d optional check%s reported a warning. Projects can still be created, but that tooling has to be installed manually.</comment>',
                $warnings,
                1 === $warnings ? '' : 's'
            ));

            return self::SUCCESS;
        }

        $output->writeln('<info>All checks passed. Your environment is ready to create SahilPHP projects.</info>');

        return self::SUCCESS;
    }

    /**
     * @param array{name: string, status: string, detail: string} $result
     */
    private function format(array $result): string
    {
        return match ($result['status']) {
            RequirementsChecker::OK => sprintf('<info>[ OK ]</info> %s', $result['detail']),
            RequirementsChecker::WARNING => sprintf('<comment>[WARN]</comment> %s', $result['detail']),
            default => sprintf('<error>[FAIL]</error> %s', $result['detail']),
        };
    }
}
