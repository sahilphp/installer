<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Handles Node.js and npm operations for asset building.
 */
final class NodeRunner
{
    public function installAndBuild(string $projectDir, OutputInterface $output): array
    {
        $result = ['success' => false, 'message' => ''];

        // Check if Node.js is available
        $nodeCheck = exec('node --version 2>&1', $nodeOutput, $nodeExitCode);
        if ($nodeExitCode !== 0 || $nodeOutput === []) {
            $result['message'] = 'Node.js not found. Run `npm install` manually later.';
            return $result;
        }

        $result['node_version'] = $nodeOutput[0];
        $output->writeln(sprintf('<info>Node.js %s detected</info>', $result['node_version']));

        // Run npm install
        $npmInstall = new Process(['npm', 'install']);
        $npmInstall->setWorkingDirectory($projectDir);
        $npmInstall->run();

        if (!$npmInstall->isSuccessful()) {
            $result['message'] = 'npm install completed with warnings. Run `npm install` manually if needed.';
            return $result;
        }

        $output->writeln('<info>npm install completed</info>');

        // Run npm build
        $npmBuild = new Process(['npm', 'run', 'build']);
        $npmBuild->setWorkingDirectory($projectDir);
        $npmBuild->run();

        $result['success'] = $npmBuild->isSuccessful();

        if ($result['success']) {
            $output->writeln('<info>Production build completed</info>');
        } else {
            $result['message'] = 'npm build failed. Check the output above for details.';
        }

        return $result;
    }
}