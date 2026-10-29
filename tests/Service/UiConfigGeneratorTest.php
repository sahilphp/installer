<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Tests\Service;

use PHPUnit\Framework\TestCase;
use Sahil\Ui\Profile;
use Sahil\Ui\ProfilePlan;
use SahilPHP\Installer\Service\UiConfigGenerator;
use SahilPHP\Installer\Ui\UiChoice;

final class UiConfigGeneratorTest extends TestCase
{
    public function testViteConfigMatchesTheFrameworkProfilePlanForEveryChoice(): void
    {
        $generator = new UiConfigGenerator();

        foreach (UiChoice::all() as $choice) {
            $profile = Profile::resolve($choice->value);

            self::assertNotNull($profile, sprintf('No framework profile matches [%s].', $choice->value));
            self::assertSame(
                ProfilePlan::build($profile)->files['vite.config.js'],
                $generator->viteConfig($choice),
                sprintf('The [%s] config must come from ProfilePlan, not a stub copy.', $choice->value),
            );
        }
    }

    public function testTailwindConfigUsesTheProfileBuildInput(): void
    {
        $config = (new UiConfigGenerator())->viteConfig(UiChoice::tailwind());

        self::assertStringContainsString("require('vite')", $config);
        self::assertStringContainsString("require('@tailwindcss/vite').default", $config);
        self::assertStringContainsString("tailwind: 'resources/css/tailwind.css'", $config);
    }

    public function testBootstrapConfigUsesTheJavaScriptEntryPoint(): void
    {
        $config = (new UiConfigGenerator())->viteConfig(UiChoice::bootstrap());

        self::assertStringContainsString("require('vite')", $config);
        self::assertStringContainsString("bootstrap: 'resources/js/bootstrap.js'", $config);
        self::assertStringNotContainsString('tailwind', $config);
    }

    public function testGeneratedConfigIsCommonJsSoViteDoesNotWarn(): void
    {
        $generator = new UiConfigGenerator();

        foreach (UiChoice::all() as $choice) {
            $config = $generator->viteConfig($choice);

            self::assertStringNotContainsString('export default', $config);
            self::assertStringNotContainsString("\nimport ", $config);
            self::assertStringContainsString('module.exports = defineConfig(', $config);
        }
    }
}
