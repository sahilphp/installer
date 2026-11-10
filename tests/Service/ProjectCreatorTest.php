<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests\Service;

use PHPUnit\Framework\TestCase;
use SahilPHP\Installer\Service\ProjectCreator;
use SahilPHP\Installer\Service\ProjectNameValidator;
use SahilPHP\Installer\Ui\UiChoice;
use SahilPHP\Installer\Ui\UiKit;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class ProjectCreatorTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/sahilphp-test-' . bin2hex(random_bytes(8));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->tempDir)) {
            $this->filesystem->remove($this->tempDir);
        }
    }

    public function testRejectsInvalidProjectName(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid project name'));

        $creator = new ProjectCreator();
        $result = $creator->create('invalid name', $this->tempDir . '/test', UiKit::None, UiChoice::tailwind(), $io);

        $this->assertFalse($result);
    }

    public function testRejectsExistingDirectory(): void
    {
        $existingDir = $this->tempDir . '/existing';
        $this->filesystem->mkdir($existingDir);

        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())
            ->method('error')
            ->with($this->stringContains('already exists'));

        $creator = new ProjectCreator();
        $result = $creator->create('existing', $existingDir, UiKit::None, UiChoice::tailwind(), $io);

        $this->assertFalse($result);
    }

    public function testNextStepsUseTheProjectCliInsteadOfARawPhpServerCommand(): void
    {
        $lines = ProjectCreator::nextSteps('test-app', UiKit::None, UiChoice::tailwind());
        $joined = implode("\n", $lines);

        $this->assertSame('cd test-app', $lines[0]);
        $this->assertStringContainsString('php sahil start', $joined);
        $this->assertStringContainsString('composer start', $joined);
        $this->assertStringNotContainsString('php -S', $joined);
        $this->assertStringNotContainsString('public/index.php', $joined);
    }

    public function testNextStepsPrintTheChosenProfileAndThePageUrls(): void
    {
        $tailwind = implode("\n", ProjectCreator::nextSteps('test-app', UiKit::React, UiChoice::tailwind()));

        $this->assertStringContainsString('UI kit: React (react)', $tailwind);
        $this->assertStringContainsString('CSS framework: Tailwind CSS v4 (tailwind)', $tailwind);
        $this->assertStringContainsString('http://127.0.0.1:8000/          welcome page', $tailwind);
        $this->assertStringContainsString('http://127.0.0.1:8000/ui-kit', $tailwind);
        $this->assertStringContainsString('http://127.0.0.1:8000/health', $tailwind);
        $this->assertStringContainsString('php sahil unda:auth', $tailwind);
        $this->assertStringContainsString('http://127.0.0.1:8000/login', $tailwind);
        $this->assertStringContainsString('http://127.0.0.1:8000/register', $tailwind);
        $this->assertStringContainsString('http://127.0.0.1:8000/dashboard', $tailwind);

        $bootstrap = implode("\n", ProjectCreator::nextSteps('test-app', UiKit::None, UiChoice::bootstrap()));

        $this->assertStringContainsString('UI kit: Sahil Engine (none)', $bootstrap);
        $this->assertStringContainsString('CSS framework: Bootstrap 5 (bootstrap)', $bootstrap);
    }

    public function testCreatedProjectUsesThePublicSahilEntryPointAndContainsNoRuntimeArtifacts(): void
    {
        $io = new SymfonyStyle(new ArrayInput([]), new NullOutput());
        $creator = new ProjectCreator();

        $this->assertTrue($creator->create(
            'runtime-check',
            $this->tempDir . '/runtime-check',
            UiKit::None,
            UiChoice::tailwind(),
            $io,
            false,
        ));

        $root = $this->tempDir . '/runtime-check';

        $this->assertFileExists($root . '/sahil');
        $this->assertDirectoryDoesNotExist($root . '/bin');
        $this->assertFileDoesNotExist($root . '/storage/logs/app.log');
        $this->assertDirectoryDoesNotExist($root . '/public/build');
        $this->assertSame([], glob($root . '/storage/framework/views/*.php') ?: []);
        $this->assertSame([], glob($root . '/storage/framework/sessions/*') ?: []);
    }

    public function testValidatesProjectNameCorrectly(): void
    {
        $validator = new ProjectNameValidator();

        $this->assertTrue($validator->isValid('my-app'));
        $this->assertTrue($validator->isValid('my_app'));
        $this->assertTrue($validator->isValid('myapp123'));
        $this->assertFalse($validator->isValid('my app'));
        $this->assertFalse($validator->isValid('my/app'));
        $this->assertFalse($validator->isValid('../etc'));
        $this->assertFalse($validator->isValid(''));
    }

    public function testProvidesHelpfulValidationErrors(): void
    {
        $validator = new ProjectNameValidator();

        $errors = $validator->getErrors('my app');
        $this->assertContains('Project name cannot contain spaces', $errors);

        $errors = $validator->getErrors('../etc');
        $this->assertContains('Project name cannot contain ".."', $errors);

        $errors = $validator->getErrors('my/app');
        $this->assertContains('Project name cannot contain path separators', $errors);
    }
}
