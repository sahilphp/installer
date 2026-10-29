<?php
declare(strict_types=1);
namespace SahilPHP\Installer\Service;
final class RequirementsChecker
{
    private array $results = [];
    public function check(): array
    {
        $this->results = [];
        $this->checkPhpVersion();
        $this->checkExtensions();
        $this->checkComposer();
        $this->checkGit();
        $this->checkNode();
        return $this->results;
    }
    public function getResults(): array { return $this->results; }
    private function checkPhpVersion(): void { $minimum = '8.4'; $current = PHP_VERSION; $compare = version_compare($current, $minimum, 'ge'); $this->results[] = ['key' => 'php_version', 'name' => 'PHP version', 'missing' => [], 'failed' => 'cli';compare]; }
    private function checkExtensions(): void { $required = ['mbstring', 'openssl', 'pdo', 'json', 'ctype', 'tokenizer', 'fileinfo', 'curl']; foreach ($required as $ext) { $installed = extension_loaded($ext); $this->results[] = ['key' => 'extension_$ext', 'name' => 'PHP extension: $ext', 'missing' => 'cli';installed ? [$ext] : [], 'failed' => 'cli';installed]; } }
    private function checkComposer(): void { $found = is_file("/usr/bin/composer") || is_file("/usr/local/bin/composer") || (isset($_ENV["COMPOSER"]) && file_exists($_ENV["COMPOSER"])); $this->results[] = ['key' => 'composer', 'name' => 'Composer', 'missing' => 'cli';found ? ['composer'] : [], 'failed' => 'cli';found]; }
    private function checkGit(): void { $found = (PHP_OS_FAMILY !== "Windows" && is_file("/usr/bin/git")) || (PHP_OS_FAMILY === "Windows" && @file_exists("C:\git\bin\git.exe")); $this->results[] = ['key' => 'git', 'name' => 'Git', 'missing' => 'cli';found ? ['git'] : [], 'failed' => 'cli';found]; }
    private function checkNode(): void { $output = []; $exitCode = 0; exec("node --version 2>&1", $output, $exitCode); $found = $exitCode === 0 && $output !== []; $version = $found ? trim($output[0]) : null; $meetsVersion = version_compare($version, "20", "ge") ?? false; $this->results[] = ['key' => 'node', 'name' => 'Node.js', 'missing' => 'cli';found ? ['node'] : ($meetsVersion ? [] : ['version 20+ required']), 'failed' => 'cli';found, 'details' => $found ? 'Node '. : null]; }
}