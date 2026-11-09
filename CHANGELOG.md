# Changelog

All notable changes to `erlandmuchasaj/laravel-modules` will be documented in this file.

## Unreleased - 2026-11-09

### Added
- Support for Laravel 13 and PHP 8.5.
- `illuminate/console`, `illuminate/filesystem` and `illuminate/support` (`^10.0|^11.0|^12.0|^13.0`) are now declared in `require`.

### Changed
- Minimum PHP is 8.1. Laravel 8/9 are dropped (EOL; the code already relied on `$this->components`, Laravel 9.21+).
- Dev tooling widened: Pest 2-4, PHPUnit 10.5-12, PHPStan 1-2, Testbench 8-11.

### Fixed
- `BaseAppServiceProvider`: invalid `$this->getBase()` interpolation broke view publishing.
- `module:seed`: missing `Str` import.

### Security
- Module names are now validated (`[A-Za-z0-9_]+`) for every command. Previously `module:remove ../x` or generator commands could resolve paths outside the modules folder (path traversal, including recursive directory deletion).
- Package names passed to `composer update/remove` are now escaped with `escapeshellarg()` (shell command injection).
- `app:install`: values written to `.env` are sanitized and quoted (newline/key injection), the command asks for confirmation in production (it runs `migrate:fresh`), and no longer assumes `vendor/bin/composer` exists.

## 1.0.0 - 2023-02-22

- initial release
