<?php

declare(strict_types=1);

namespace Tests\Support;

use Sahil\Support\EnvironmentFile;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Sahil\Support\EnvironmentFile
 */
final class EnvironmentFileTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/sahil-env-' . bin2hex(random_bytes(6));

        if (!mkdir($this->directory, 0o777, true) && !is_dir($this->directory)) {
            self::fail('Unable to create a temporary directory.');
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->directory);
    }

    private function file(): EnvironmentFile
    {
        return new EnvironmentFile($this->directory . '/.env');
    }

    public function test_absent_files_and_keys_read_as_null(): void
    {
        self::assertFalse($this->file()->exists());
        self::assertNull($this->file()->get('APP_KEY'));
    }

    public function test_setting_a_key_creates_the_file_without_overwriting_anything(): void
    {
        $file = $this->file();
        file_put_contents($file->path(), "# comment\nAPP_ENV=local\nDB_HOST=127.0.0.1\n");

        self::assertSame($file->path(), $file->set('APP_KEY', 'base64:abc'));

        self::assertSame("# comment\nAPP_ENV=local\nDB_HOST=127.0.0.1\nAPP_KEY=base64:abc\n", file_get_contents($file->path()));
        self::assertSame('base64:abc', $file->get('APP_KEY'));
        self::assertSame('local', $file->get('APP_ENV'));
        self::assertFileDoesNotExist($file->path() . '.tmp');
    }

    public function test_setting_an_existing_key_replaces_it_in_place(): void
    {
        $file = $this->file();
        file_put_contents($file->path(), "APP_KEY=old\nAPP_ENV=local\n");

        $file->set('APP_KEY', 'new');

        self::assertSame("APP_KEY=new\nAPP_ENV=local\n", file_get_contents($file->path()));
    }

    public function test_quoted_values_are_unquoted_when_read(): void
    {
        $file = $this->file();
        file_put_contents($file->path(), "APP_KEY=\"base64:quoted\"\nOTHER='plain'\n");

        self::assertSame('base64:quoted', $file->get('APP_KEY'));
        self::assertSame('plain', $file->get('OTHER'));
    }

    public function test_commented_assignments_are_ignored(): void
    {
        $file = $this->file();
        file_put_contents($file->path(), "# APP_KEY=commented\n");

        self::assertNull($file->get('APP_KEY'));

        $file->set('APP_KEY', 'base64:real');
        self::assertStringContainsString("# APP_KEY=commented\n", (string) file_get_contents($file->path()));
        self::assertStringEndsWith("APP_KEY=base64:real\n", (string) file_get_contents($file->path()));
    }
}
