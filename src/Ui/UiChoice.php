<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Ui;

/**
 * CSS framework choice for new project creation.
 *
 * This is deliberately separate from {@see UiKit}: project creation always
 * asks both questions before writing any file.
 */
final class UiChoice
{
    public function __construct(
        public readonly string $value,
        public readonly string $label,
    ) {
    }

    public static function tailwind(): self
    {
        return new self('tailwind', 'Tailwind CSS v4');
    }

    public static function bootstrap(): self
    {
        return new self('bootstrap', 'Bootstrap 5');
    }

    public static function fromString(string $value): ?self
    {
        return match (strtolower(trim($value))) {
            'tailwind', 'tailwindcss', 'tailwind css v4', 'tailwind css' => self::tailwind(),
            'bootstrap', 'bootstrap5', 'bootstrap 5' => self::bootstrap(),
            default => null,
        };
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return [self::tailwind(), self::bootstrap()];
    }

    /**
     * @return array<int, string> numbered choices for a ChoiceQuestion
     */
    public static function numberedChoices(): array
    {
        return [
            1 => self::tailwind()->label,
            2 => self::bootstrap()->label,
        ];
    }
}
