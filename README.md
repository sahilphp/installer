# SahilPHP Installer

The Composer-installed CLI for creating SahilPHP projects.

## Installation

```bash
composer global require sahilphp/installer
```

Make sure Composer's global bin directory (`composer global config bin-dir --absolute`)
is on your `PATH`, so the `sahilphp` executable is found.

## Commands

### `sahilphp doctor`

Checks that the machine can run SahilPHP and create projects:

- the PHP version (8.4 or newer) and the required PHP extensions
  (`curl`, `fileinfo`, `mbstring`, `openssl`);
- Composer, and the optional frontend tooling Node.js/npm and git;
- whether the current directory is writable.

Each check prints one actionable line. The command exits with status `1` when a
required check fails and with status `0` otherwise; missing optional tools are
reported as warnings. No credentials or environment secrets are printed.

### `sahilphp new`

Creates a new SahilPHP project in the current directory.

### `sahilphp list`, `sahilphp --help`

List the available commands and their options.

## Development

```bash
composer install
composer test      # phpunit
composer lint      # php -l over src and tests
```
