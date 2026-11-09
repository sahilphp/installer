# Changelog

All notable changes to `sahilphp/installer` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- `--ui-kit=firefiber|react|vue|svelte|none` and
  `--css=tailwind|bootstrap` options on `sahilphp new`.
- Interactive `ChoiceQuestion` prompts for both choices, in order, accepting a
  number or a name and re-asking on invalid input.
- The installer passes both choices to the starter kit through
  `SAHILPHP_UI_KIT`, `SAHILPHP_CSS` and `SAHILPHP_INSTALLER`, so the user is
  never asked twice.
- Guard against using a UI package name as a project name, with a friendly
  "did you mean sahilphp new my-app?" error.

### Fixed

- Project creation now records a UI kit and a CSS framework separately
  (previously only Tailwind/Bootstrap was asked).
- Non-interactive runs default to `none` + `tailwind` and print a notice.

### Changed

- The starter kit applies the choices through `php sahil ui:setup`, which
  copies only the selected stubs and deletes `stubs/` afterwards.
- All four UI packages (`sahilphp/firefiber`, `sahilphp/react`,
  `sahilphp/vue`, `sahilphp/svelte`) are libraries; `composer create-project`
  on them exits 1 with installation guidance.
