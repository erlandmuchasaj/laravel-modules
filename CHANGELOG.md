# Changelog

All notable changes to `erlandmuchasaj/laravel-modules` will be documented in this file.

## 1.4.0 - 2026-11-12

### Added
- Livewire 3/4 (optional): components in a module's `src/Livewire` are registered automatically under the module alias (`<livewire:blog::counter />`). No effect when Livewire is not installed or the module ships no components.
- Filament 3/4/5 (optional): `ErlandMuchasaj\Modules\Filament\ModulesPlugin` discovers module resources, pages, widgets and clusters.
- Generators honour `modules.namespace` and a custom `modules.folder` everywhere (tests, factories, seeders, module `composer.json`, generated provider). Defaults are unchanged.

### Fixed
- `module:make-test`: choosing a Pest test from the interactive prompt threw `UnhandledMatchError`.
- `module:extract-translation`: no longer uses `GLOB_BRACE`, which is undefined on musl based systems (Alpine Linux).
- `module:make-controller --requests`: wrong (double backslash) form request namespace.
- A module without `config/config.php` no longer breaks registration.
- Blade component namespace and module seeder listing work with a custom root namespace.
- `module:make` rejects names starting with a digit (they produced an invalid PHP namespace).
- `app:install`: `.env.example` is resolved from the application root instead of the current working directory.
- Removed PHP 8.5 deprecated `ReflectionMethod::setAccessible()` calls (tests) and reflection on private orchestrator methods (seed commands).

### Security
- `module:make-migration` and `module:extract-translation` now validate the module name (`[A-Za-z0-9_]+`) like the other commands (path traversal).
- Package names passed to `composer update/remove` are escaped with `escapeshellarg()` (shell command injection).

## 1.3.0 - 2026-11-09

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
- Module names are now validated (`[A-Za-z0-9_]+`). Previously `module:remove ../x` or generator commands could resolve paths outside the modules folder (path traversal, including recursive directory deletion).
- `app:install`: values written to `.env` are sanitized and quoted (newline/key injection), the command asks for confirmation in production (it runs `migrate:fresh`), and no longer assumes `vendor/bin/composer` exists.

## 1.0.0 - 2023-02-22

- initial release
