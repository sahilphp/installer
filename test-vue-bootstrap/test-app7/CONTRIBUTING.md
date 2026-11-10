# Contributing to the SahilPHP starter kit

Thanks for taking the time to contribute. This document covers the starter
kit; the framework package (`sahilphp/framework`) has its own copy.

## Requirements

- PHP **8.4 or newer** (CI runs 8.4 and 8.5)
- Composer 2.x
- Git — the framework is normally resolved from Packagist

## Getting started

```bash
git clone https://github.com/sahilphp/sahilphp.git
cd sahilphp
composer install
cp .env.example .env
php sahil key:generate
php sahil storage:prepare
composer start
```

When you also work on the framework, clone it next to this repository and add
a temporary path repository instead of installing from Packagist:

```bash
composer config repositories.framework '{"type":"path","url":"../framework","options":{"symlink":true,"versions":{"sahilphp/framework":"1.1.0"}}}'
composer update sahilphp/framework
```

Remove that entry (`composer config --unset repositories.framework`) before
opening a pull request; the released `composer.json` must not contain it.

## Quality gates

```bash
composer test        # PHPUnit (application suite)
composer validate --strict
```

Optional frontend checks: `npm ci && php sahil ui:status`.

## Pull requests

- One focused change per pull request, with a description of the behaviour it
  changes and how you verified it.
- Add or update tests under `tests/` for any behaviour change.
- Update `CHANGELOG.md` under `## [Unreleased]`.
- Never commit `.env` files, credentials, `vendor/`, build output or storage
  contents.

## Reporting bugs and vulnerabilities

Bugs: open a GitHub issue with a minimal reproduction (include
`php sahil about` output).
Security issues: **do not** open a public issue — follow `SECURITY.md`.

## License

By contributing you agree that your contribution is licensed under the MIT
License (see `LICENSE`).
