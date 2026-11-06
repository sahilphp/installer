<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Ui;

/**
 * Frontend UI kit chosen when creating a project.
 *
 * The CSS framework is a separate choice ({@see UiChoice}); the two answers
 * are passed to the starter kit through SAHILPHP_UI_KIT and SAHILPHP_CSS.
 */
enum UiKit: string
{
    case Firefiber = 'firefiber';
    case React = 'react';
    case Vue = 'vue';
    case Svelte = 'svelte';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Firefiber => 'Firefiber (server-driven reactive UI, like Livewire)',
            self::React => 'React (React + Vite)',
            self::Vue => 'Vue (Vue 3 + Vite)',
            self::Svelte => 'Svelte (Svelte + Vite)',
            self::None => 'Sahil Engine (native SahilPHP template engine)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Firefiber => 'Firefiber',
            self::React => 'React',
            self::Vue => 'Vue',
            self::Svelte => 'Svelte',
            self::None => 'Sahil Engine',
        };
    }

    /**
     * Resolve a CLI value or a human-readable name.
     */
    public static function fromName(string $value): ?self
    {
        $normalised = strtolower(trim($value));

        foreach (self::cases() as $case) {
            if ($normalised === $case->value || $normalised === strtolower($case->shortLabel())) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $kit): string => $kit->value, self::cases());
    }

    /**
     * @return array<int, string> numbered choices for a ChoiceQuestion
     */
    public static function numberedChoices(): array
    {
        $choices = [];

        foreach (self::cases() as $index => $case) {
            $choices[$index + 1] = $case->label();
        }

        return $choices;
    }
}
