{{--
    Laravel Modules Guidelines for AI Code Assistants
    Source: https://github.com/erlandmuchasaj/laravel-modules
    License: MIT
--}}
## Laravel Modules

- `erlandmuchasaj/laravel-modules` splits the application into self-contained modules that live in `modules/<ModuleName>`. Each module is a composer path package (namespace `Modules\<ModuleName>`) with its own routes, views, translations, config, migrations, factories, seeders and tests. A module mirrors the Laravel folder structure but uses `src/` instead of `app/`.
- Always activate the `laravel-modules-development` skill when creating or removing a module, when adding or changing any file under `modules/`, or when running a `module:*` Artisan command.
- If the skill is unavailable, read `vendor/erlandmuchasaj/laravel-modules/resources/boost/skills/laravel-modules-development/SKILL.md` before making changes.
- Code that belongs to a module is generated with `php artisan module:make-<type> <ModuleName> <ClassName>`, never with the `make:<type>` commands, which write into `app/`.
