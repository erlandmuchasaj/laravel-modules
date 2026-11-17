---
name: laravel-modules-development
description: Builds and maintains application modules with erlandmuchasaj/laravel-modules. Use when creating or removing a module, running module:* Artisan commands, or adding or changing anything under modules/ such as module service providers, routes, controllers, models, views, translations, config, migrations, factories, seeders, tests, Livewire components or Filament resources.
license: MIT
metadata:
  author: Erland Muchasaj
---

# Laravel Modules Development

## Overview

`erlandmuchasaj/laravel-modules` organises an application into modules. A module is a small Laravel application inside
the application: a composer path package in `modules/<ModuleName>` with the namespace `Modules\<ModuleName>` and a
Laravel-like folder structure that uses `src/` instead of `app/`. Modules are wired automatically: there is nothing to
register in `config/app.php` or `bootstrap/providers.php`.

## When to Activate

- Creating, listing, analysing or removing a module.
- Generating a class, migration, view or test that belongs to a module.
- Editing any file under `modules/` (providers, routes, controllers, models, views, lang files, config, seeders, ...).
- Registering module middleware, policies, observers, commands, event listeners or container bindings.
- Seeding module data or changing the order in which module seeders run.
- Adding Livewire components or Filament resources to a module.

## Scope

- In scope: everything inside `modules/`, the `module:*` Artisan commands, the module's service providers, and the
  root `composer.json` entries the package manages (`modules/<name>` requirements and the `./modules/*` path repository).
- Out of scope: code in `app/` that is not part of a module, and the package's own source in
  `vendor/erlandmuchasaj/laravel-modules` (never edit it).

## First Steps

1. Run `php artisan module:list` to see which modules exist.
2. Check `config('modules.folder')` (default `modules`) and `config('modules.namespace')` (default `Modules`) if the
   project published `config/modules.php`. Every path and namespace below assumes the defaults.
3. Look at an existing module before adding to it, and follow its conventions.

## Naming Conventions

Module names may only contain letters, digits and underscores and must not start with a digit. Always pass the
StudlyCase name to commands.

| What | Example for `BlogPost` | Used for |
|------|------------------------|----------|
| Module name (StudlyCase) | `BlogPost` | folder `modules/BlogPost`, namespace `Modules\BlogPost`, the `{module}` command argument |
| Module alias (snake_case) | `blog_post` | views, Blade components, translations, config and Livewire: `blog_post::...` |
| Package name (kebab-case) | `modules/blog-post` | composer package name, default route URL `/blog-post` and route name `blog-post` |

For a single-word module (`Blog`) all three are `blog`.

## Module Structure

```text
modules/Blog/
├── composer.json                     # package "modules/blog", PSR-4 autoload, provider auto-discovery
├── config/config.php                 # config('modules.blog.config.*')
├── database/
│   ├── factories/                    # Modules\Blog\Database\Factories
│   ├── migrations/                   # loaded automatically
│   └── seeders/DatabaseSeeder.php    # Modules\Blog\Database\Seeders
├── lang/en.json, lang/en/messages.php
├── resources/assets/{css,js}/
├── resources/views/                  # view('blog::...')
├── routes/{web,api,channels}.php
├── src/                              # Modules\Blog
│   ├── Console/Commands/
│   ├── Http/{Controllers,Middleware,Requests,Resources,ViewComposers}/
│   ├── Models/
│   ├── Providers/{App,Event,Route,Seed}ServiceProvider.php
│   └── helpers.php                   # autoloaded helper functions
└── tests/{Feature,Unit}/             # Modules\Blog\Tests
```

Other `src/` folders (`Events`, `Jobs`, `Listeners`, `Mail`, `Notifications`, `Observers`, `Policies`, `Rules`,
`Casts`, `Exceptions`, `Broadcasting`, `Traits`, `View/Components`, `Livewire`, `Filament`, ...) are created by the
generators when needed.

## Creating and Removing Modules

```bash
php artisan module:make Blog                 # generate + add to composer.json + composer update
php artisan module:make Blog --all           # also create every optional src/ folder
php artisan module:make Blog --no-register   # generate files only, do not touch composer
php artisan module:remove Blog --force       # delete the folder and remove it from composer.json
```

`module:make` adds `"modules/blog": "*"` to the root `require`, adds the `./modules/*` path repository (symlinked) and
runs `composer update modules/blog`. Laravel's package discovery then loads
`Modules\Blog\Providers\AppServiceProvider`. Do not add the provider anywhere by hand.

`module:remove` is destructive: it deletes the module folder permanently and does not roll back migrations. Only run it
when the user asked for it; without `--force` it asks for confirmation.

## Generators

Use these instead of `make:*` (which writes to `app/`) and instead of creating files by hand. The module comes first,
the name second. Always pass both arguments and add `--no-interaction` so the command never waits for a prompt.

```bash
php artisan module:make-model Blog Post --migration --factory --no-interaction
```

| Command | Location inside `modules/<Module>/` | Notable options |
|---------|-------------------------------------|-----------------|
| `module:make-model` | `src/Models/<Name>/<Name>.php` | `--all`, `--migration`, `--factory`, `--seed`, `--controller`, `--resource`, `--api`, `--requests`, `--policy`, `--pivot`, `--traits`, `--test`, `--pest` |
| `module:make-controller` | `src/Http/Controllers` | `--api`, `--invokable`, `--model=`, `--parent=`, `--resource`, `--requests`, `--singleton` |
| `module:make-request` | `src/Http/Requests` | |
| `module:make-resource` | `src/Http/Resources` | `--collection` |
| `module:make-middleware` | `src/Http/Middleware` | |
| `module:make-migration` | `database/migrations` | `--create=`, `--table=` |
| `module:make-factory` | `database/factories` | `--model=` |
| `module:make-seeder` | `database/seeders` | |
| `module:make-policy` | `src/Policies` | `--model=`, `--guard=` |
| `module:make-observer` | `src/Observers` | `--model=` |
| `module:make-scope` | `src/Models/Scopes` | |
| `module:make-cast` | `src/Casts` | `--inbound` |
| `module:make-trait` | `src/Traits`, or next to a model with `--model=` | |
| `module:make-event` | `src/Events` | |
| `module:make-listener` | `src/Listeners` | `--event=`, `--queued` |
| `module:make-job` | `src/Jobs` | `--sync` |
| `module:make-mail` | `src/Mail` | `--markdown=` |
| `module:make-notification` | `src/Notifications` | `--markdown=` |
| `module:make-channel` | `src/Broadcasting` | |
| `module:make-command` | `src/Console/Commands` | `--command=` |
| `module:make-rule` | `src/Rules` | `--implicit` |
| `module:make-exception` | `src/Exceptions` | `--render`, `--report` |
| `module:make-provider` | `src/Providers` | |
| `module:make-component` | `src/View/Components` + `resources/views/components` | `--inline`, `--view` |
| `module:make-view` | `resources/views` (dot notation: `pages.index`) | `--extension=` |
| `module:make-test` | `tests/Feature`, or `tests/Unit` with `--unit` | `--pest` |

Most generators accept `--force` to overwrite. Run any command with `--help` for the complete option list.
A stub placed in the application's `stubs/` directory (for example `stubs/controller.stub`) overrides the bundled one.

Generating a file does not register it. After generating a policy, observer, middleware, command, provider or
listener, register it in the module's providers (see below).

## Service Providers

Each module has four providers in `modules/<Module>/src/Providers`. Only `AppServiceProvider` is discovered by
Laravel; it loads the others through `$providers`.

### AppServiceProvider

Extends `ErlandMuchasaj\Modules\Providers\BaseAppServiceProvider`. Register things by adding to its arrays:

```php
namespace Modules\Blog\Providers;

use ErlandMuchasaj\Modules\Providers\BaseAppServiceProvider;
use Modules\Blog\Console\Commands\PruneDrafts;
use Modules\Blog\Http\Middleware\EnsurePostIsPublished;
use Modules\Blog\Models\Post\Post;
use Modules\Blog\Observers\PostObserver;
use Modules\Blog\Policies\PostPolicy;

class AppServiceProvider extends BaseAppServiceProvider
{
    protected string $module = 'Blog';

    protected array $providers = [
        RouteServiceProvider::class,
        EventServiceProvider::class,
        SeedServiceProvider::class,
    ];

    protected array $policies = [Post::class => PostPolicy::class];          // Model => Policy

    protected array $observers = [Post::class => PostObserver::class];       // Model => Observer

    protected array $aliases = [];                                           // alias => class

    protected array $middleware = [];                                        // global middleware

    protected array $middlewareGroups = ['web' => [], 'api' => []];          // appended to the group

    protected array $routeMiddleware = ['published' => EnsurePostIsPublished::class]; // alias => middleware

    protected array $commands = [PruneDrafts::class];                        // Artisan commands
}
```

For anything else override a hook method rather than `boot()` / `register()`:

| Hook | Phase | Use for |
|------|-------|---------|
| `registerBindings()` | register | container bindings and singletons |
| `registerFacades()` | register | facade bindings |
| `bootValidators()` | boot | custom validation rules |
| `bootBladeDirective()` | boot | Blade directives |
| `bootServices()` | boot | view composers, macros, anything else |

```php
protected function registerBindings(): void
{
    $this->app->singleton(PostRepository::class, EloquentPostRepository::class);
}
```

If `boot()` or `register()` is overridden, it must call the parent method; the parent loads the module's migrations,
views, Blade components, translations and config.

### EventServiceProvider

A standard Laravel event service provider: map events to listeners in `$listen` and subscribers in `$subscribe`.
Event discovery is disabled, so listeners must be listed explicitly.

### RouteServiceProvider

Extends `BaseRouteServiceProvider` and loads `routes/web.php`, `routes/api.php` and `routes/channels.php`.

| File | Middleware | URL prefix | Name prefix |
|------|------------|------------|-------------|
| `routes/web.php` | `web` | none | none |
| `routes/api.php` | `api` | `api` | `api.` |

Override `$apiPrefix`, `$apiNamePrefix`, `$webMiddleware` or `$apiMiddleware` in the module's `RouteServiceProvider`
to change these. Reference controllers by class in route files:

```php
use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\PostController;

Route::resource('posts', PostController::class);
```

Give module routes unique names (for example `blog.posts.index`): route names are global across modules.

### SeedServiceProvider

Extends `BaseSeedServiceProvider`. See [Seeding](#seeding).

## Views, Components, Translations and Config

All are namespaced with the module alias (snake_case).

```php
view('blog::pages.index');                      // modules/Blog/resources/views/pages/index.blade.php
__('blog::messages.welcome');                   // modules/Blog/lang/en/messages.php
__('Read more');                                // modules/Blog/lang/en.json
config('modules.blog.config.posts_per_page');   // modules/Blog/config/config.php
```

```blade
{{-- modules/Blog/src/View/Components/Alert.php or resources/views/components/alert.blade.php --}}
<x-blog::alert type="success" />

@include('blog::partials.comment-form')
```

`php artisan module:extract-translation Blog` scans the module for translation strings and adds missing keys to its
`lang/*.json` files.

## Database

### Migrations

`php artisan module:make-migration Blog create_posts_table` writes to `modules/Blog/database/migrations`. Module
migrations are loaded automatically, so `php artisan migrate` runs them. Never copy them to `database/migrations`.

### Models and Factories

Each model lives in its own folder: `module:make-model Blog Post --factory` creates

- `modules/Blog/src/Models/Post/Post.php` → `Modules\Blog\Models\Post\Post`
- `modules/Blog/database/factories/Post/PostFactory.php` → `Modules\Blog\Database\Factories\Post\PostFactory`

Laravel cannot guess a module factory from the model name, so every module model that uses `HasFactory` must define
`newFactory()`. `module:make-model --factory` (and `--all`) generates it; add it by hand to models created without
the flag:

```php
namespace Modules\Blog\Models\Post;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Blog\Database\Factories\Post\PostFactory;

class Post extends Model
{
    use HasFactory;

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
```

A factory generated separately is written to the folder matching its namespace (`database/factories/Post/PostFactory.php`):

```bash
php artisan module:make-factory Blog Post --model=Post --no-interaction
```

Models in other modules are referenced by their full class name; declare the relationship normally.

### Seeding

Each module has `database/seeders/DatabaseSeeder.php`. Add new seeders with `module:make-seeder` and call them from
that `DatabaseSeeder`:

```php
public function run(): void
{
    $this->call([
        CategorySeeder::class,
        PostSeeder::class,
    ]);
}
```

Module seeders run automatically after the application's `DatabaseSeeder` on `php artisan db:seed`,
`migrate:fresh --seed` and `migrate:refresh --seed`. They do not run when a specific seeder class is requested
(`db:seed --class=...`, `--seeder=...`). Do not call module seeders from the application's `DatabaseSeeder` as well,
or they will run twice.

Order is set in the module's `SeedServiceProvider`:

```php
protected string $namespace = 'Modules\\Shop\\Database\\Seeders\\DatabaseSeeder'; // '' disables auto seeding
protected int $priority = 100;                                                     // higher runs first
protected array $dependencies = [                                                  // must run before this one
    'Modules\\Blog\\Database\\Seeders\\DatabaseSeeder',
];
```

Dependencies take precedence over priority. Useful commands:

| Command | Purpose |
|---------|---------|
| `module:seed Blog` | Seed one module (`--force` in production). |
| `module:seed-list` | Show registered module seeders in execution order (`--json`). |
| `module:seed-check` | Detect circular or missing seeder dependencies. |

## Testing

`php artisan module:make-test Blog PostTest --no-interaction` creates `modules/Blog/tests/Feature/PostTest.php`
(`--unit` for a unit test, `--pest` for Pest). Module tests only run if the application's `phpunit.xml` includes
them; add this once if it is missing:

```xml
<testsuite name="Modules">
    <directory>modules/*/tests</directory>
</testsuite>
```

Generated tests extend the application's `Tests\TestCase`. Composer only loads `autoload-dev` of the root package, so
if a module has its own test helpers (for example `Modules\Blog\Tests\TestCase`), add
`"Modules\\Blog\\Tests\\": "modules/Blog/tests/"` to `autoload-dev` in the application's `composer.json` and run
`composer dump-autoload`.

Run a single module's tests with `php artisan test modules/Blog/tests`.

## Livewire and Filament (optional)

Only relevant when the application has Livewire or Filament installed.

- Livewire 3/4: classes in `modules/Blog/src/Livewire` are registered automatically under the module alias:
  `Modules\Blog\Livewire\Forms\CreatePost` → `<livewire:blog::forms.create-post />`. Views go in
  `resources/views/livewire` (`view('blog::livewire.counter')`). No manual `Livewire::component()` call is needed.
- Filament 3/4/5: resources, pages, widgets and clusters in `modules/Blog/src/Filament/{Resources,Pages,Widgets,Clusters}`
  are discovered once the panel registers the plugin:

```php
use ErlandMuchasaj\Modules\Filament\ModulesPlugin;

$panel->plugin(ModulesPlugin::make());                    // all modules
$panel->plugin(ModulesPlugin::make()->only(['Blog']));    // or ->except([...]) / ->directory('Filament/Admin')
```

## Inspection Commands

| Command | Purpose |
|---------|---------|
| `module:list` (`--detailed`) | List modules with migration and view counts. |
| `module:analyze {module?}` | Report issues and suggestions for one or all modules. |
| `module:cache`, `module:cache-clear`, `module:cache-stats` | Manage the cache used by the two commands above. |

This cache only feeds `module:list` and `module:analyze`; it does not affect how modules load at runtime. Run
`module:cache-clear` if `module:list` does not show a module that exists on disk.

## Common Pitfalls

- Using `make:model`, `make:controller`, etc. for module code. They write to `app/`; use `module:make-*`.
- Creating a module by copying a folder. It will not be in the root `composer.json`; use `module:make`, or add
  `"modules/<name>": "*"` and run `composer update modules/<name>`.
- Registering a module provider in `bootstrap/providers.php` or `config/app.php`. It is already auto-discovered
  through the module's `composer.json`.
- Forgetting `newFactory()` on a module model created without `--factory`, which makes `Model::factory()` fail with a
  class-not-found error.
- Mixing the alias and the package name for multi-word modules: views and translations use `blog_post::`, the URL and
  composer package use `blog-post`.
- Generating a policy, observer, middleware or command and not adding it to the `AppServiceProvider` arrays.
- Overriding `boot()` or `register()` in a module provider without calling the parent.
- Editing files under `vendor/modules/...`. That folder is a symlink to `modules/`; always edit under `modules/`.
- Changing a module's `composer.json` (new dependency or autoload entry) without running
  `composer update modules/<name>` afterwards.
- Running `app:install` casually: it runs `migrate:fresh` and drops every table.
- Adding keys to `config/modules.php` and expecting an effect. Only `namespace` and `folder` exist; `paths`, `seeds`,
  `discovery`, `cache` and `composer` in older published configs were never read.
