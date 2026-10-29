<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests\Service;

use PHPUnit\Framework\TestCase;
use SahilPHP\Installer\Service\ProjectCreator;
use SahilPHP\Installer\Service\ProjectNameValidator;
use SahilPHP\Installer\Ui\UiChoice;
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
        $result = $creator->create('invalid name', $this->tempDir . '/test', UiChoice::tailwind(), $io);

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
        $result = $creator->create('existing', $existingDir, UiChoice::tailwind(), $io);

        $this->assertFalse($result);
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
