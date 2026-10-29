<?php

declare(strict_types=1);

namespace SahilPHP\Installer\Service;

use InvalidArgumentException;
use RuntimeException;
use Sahil\Ui\Profile;
use Sahil\Ui\ProfilePlan;
use SahilPHP\Installer\Ui\UiChoice;

/**
 * Generates a project's Vite configuration from the framework.
 *
 * `Sahil\Ui\ProfilePlan` is the single definition of what a UI profile
 * generates. `bin/sahil ui:install` writes the same files at runtime, so a
 * project created by `sahilphp new` and one reconfigured with `ui:install`
 * always end up with the same configuration.
 */
final class UiConfigGenerator
{
    public function viteConfig(UiChoice $ui): string
    {
        $profile = Profile::resolve($ui->value);

        if ($profile === null) {
            throw new InvalidArgumentException(sprintf('Unsupported UI profile [%s].', $ui->value));
        }

        $files = ProfilePlan::build($profile)->files;

        if (!isset($files['vite.config.js'])) {
            throw new RuntimeException(sprintf(
                'The [%s] UI profile does not generate vite.config.js.',
                $profile->value,
            ));
        }

        return $files['vite.config.js'];
    }
}
