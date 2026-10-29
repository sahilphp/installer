<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Ui;

use backedEnumerator;

/**
 * UI framework choice for new project creation.
 */
#[backed('string')]
final class UiChoice
{
    public function __construct(
        public string $value,
        public string $label,
    ) {
    }

    public static function tailwind(): self
    {
        return new self('tailwind', 'Tailwind CSS');
    }

    public static function bootstrap(): self
    {
        return new self('bootstrap', 'Bootstrap 5');
    }

    public static function fromString(string $value): ?self
    {
        return match ($value) {
            'tailwind' => self::tailwind(),
            'bootstrap' => self::bootstrap(),
            default => null,
        };
    }

    public function __toString(): string
    {
        return $this->value;
    }
}