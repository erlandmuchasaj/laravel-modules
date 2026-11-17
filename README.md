# Laravel modules

A simple, auto-wired module generator for Laravel that follows Laravel's own conventions and folder structure.
If you like Laravel, you will love **laravel modules** and feel right at home.

A module is a small Laravel application inside your Laravel application: it has its own routes, controllers, models,
views, translations, config, migrations, factories, seeders and tests. The folder structure mirrors a Laravel project;
the only difference is that you have **src/** instead of **app/**.

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [How a module is registered](#how-a-module-is-registered)
- [Module structure](#module-structure)
- [Naming conventions](#naming-conventions)
- [Artisan commands](#artisan-commands)
- [Service providers](#service-providers)
- [Routes](#routes)
- [Views, components, translations and config](#views-components-translations-and-config)
- [Database](#database)
- [Publishing module resources](#publishing-module-resources)
- [Customizing stubs](#customizing-stubs)
- [Testing modules](#testing-modules)
- [Configuration](#configuration)
- [Livewire, Filament and Alpine.js](#livewire-filament-and-alpinejs)
- [AI assistance with Laravel Boost](#ai-assistance-with-laravel-boost)

## Requirements

| Package | Supported versions |
|---------|--------------------|
| PHP     | 8.1, 8.2, 8.3, 8.4, 8.5 |
| Laravel | 10.x, 11.x, 12.x, 13.x |

Laravel 8 and 9 are end-of-life and no longer supported (Laravel 13 requires PHP 8.3+).

## Installation

Install the package via composer:

```shell
composer require erlandmuchasaj/laravel-modules
```

The service provider and the `Module` facade are auto-discovered.

Optionally publish the configuration file to `config/modules.php`:

```shell
php artisan vendor:publish --provider="ErlandMuchasaj\Modules\ModulesServiceProvider" --tag=config
```

## Quick start

```shell
# 1. Create a module (registers it in composer.json and runs composer update for you)
php artisan module:make Blog

# 2. Generate what you need inside it
php artisan module:make-model Blog Post --migration --factory --seed
php artisan module:make-controller Blog PostController --model=Post --requests
php artisan module:make-view Blog pages.index

# 3. Run the module's migrations like any other migration
php artisan migrate
```

Open `/blog` in the browser: every new module ships with a working web route (`/blog`) and API route (`/api/blog`).

## How a module is registered

Every module is a regular composer package living in `modules/<ModuleName>` and installed through a
[path repository](https://getcomposer.org/doc/05-repositories.md#path). When you run `module:make` the command:

1. Generates the module in `modules/<ModuleName>` with its own `composer.json` (package name `modules/<module-name>`).
2. Adds the package to the `require` section of your application's `composer.json`:

    ```json
    "require": {
        "modules/blog": "*"
    }
    ```

3. Adds the path repository (once):

    ```json
    "repositories": [
        {
            "type": "path",
            "url": "./modules/*",
            "options": {
                "symlink": true
            }
        }
    ]
    ```

4. Runs `composer update modules/blog` so the module is symlinked into `vendor/` and its
   `Modules\Blog\Providers\AppServiceProvider` is picked up by Laravel's package auto-discovery.

There is nothing to add to `config/app.php` or `bootstrap/providers.php`.

Use `--no-register` to only generate the files; then add the package to `composer.json` and run `composer update` yourself.

## Module structure

`php artisan module:make Blog` generates:

```text
modules/Blog/
├── composer.json                 # package "modules/blog", PSR-4 autoloading, provider auto-discovery
├── config/config.php             # config('modules.blog.config.*')
├── database/
│   ├── factories/                # Modules\Blog\Database\Factories
│   ├── migrations/               # loaded automatically
│   └── seeders/DatabaseSeeder.php  # Modules\Blog\Database\Seeders
├── lang/
│   ├── en.json                   # JSON translations
│   └── en/messages.php           # __('blog::messages.key')
├── resources/
│   ├── assets/{css,js}/
│   └── views/                    # view('blog::...') - components, errors, layouts, pages, partials
├── routes/
│   ├── web.php
│   ├── api.php
│   └── channels.php
├── src/                          # Modules\Blog (the module's "app" folder)
│   ├── Console/Commands/
│   ├── Http/{Controllers,Middleware,Requests,Resources,ViewComposers}/
│   ├── Models/
│   ├── Providers/
│   │   ├── AppServiceProvider.php    # the entry point of the module
│   │   ├── EventServiceProvider.php
│   │   ├── RouteServiceProvider.php
│   │   └── SeedServiceProvider.php
│   └── helpers.php               # autoloaded, put module helper functions here
├── tests/{Feature,Unit}/         # Modules\Blog\Tests
└── README.md, CHANGELOG.md, LICENSE.md, ...
```

`php artisan module:make Blog --all` additionally creates empty `src/` folders for `Enums`, `Broadcasting`,
`Exceptions`, `Events`, `Jobs`, `Listeners`, `Mail`, `Notifications`, `Observers`, `Policies`, `Repositories`, `Rules`,
`Services`, `Traits`, `Utils` and `Validators`. Without `--all` the generators create those folders on demand.

## Naming conventions

Module names may only contain letters, digits and underscores, and cannot start with a digit. For a module named `BlogPost`:

| What | Value | Used for |
|------|-------|----------|
| Module name (StudlyCase) | `BlogPost` | folder `modules/BlogPost`, namespace `Modules\BlogPost`, the `{module}` argument of every command |
| Module alias (snake_case) | `blog_post` | views, Blade components, translations, config, Livewire: `blog_post::...`, `config('modules.blog_post.config')` |
| Package name (kebab-case) | `modules/blog-post` | composer package name and the default route URL (`/blog-post`) |

For a single-word module such as `Blog`, all three are simply `blog`.

## Artisan commands

### Managing modules

| Command | Description |
|---------|-------------|
| `module:make {module}` | Create a new module and register it in `composer.json`. |
| `module:remove {module}` | Delete a module's folder and remove it from `composer.json` (asks for confirmation). |
| `module:list` | List all modules. `--detailed` adds migration and view counts, config presence and size. |
| `module:analyze {module?}` | Inspect one or all modules and print issues and suggestions. |

`module:make` options:

| Option | Description |
|--------|-------------|
| `--all`, `-a` | Generate the full folder structure. |
| `--no-register` | Do not touch `composer.json` and do not run composer. |
| `--optimize`, `-o` | Pass `-o` (optimized autoloader) to composer. |
| `--quiet`, `-q` | Suppress composer output. |
| `--cleanup-on-error` | Remove the partially created module if something fails. |

`module:remove` options: `--force` (`-f`, skip the confirmation), `--optimize` (`-o`) and `--quiet` (`-q`).

### Generators

Every generator takes the module name first and the class name second:

```shell
php artisan module:make-<type> {module} {name} [options]
```

They accept the same options as their `make:*` counterparts in Laravel (run any of them with `--help` for the full list).

| Command | Generates in `modules/<Module>/` | Notable options |
|---------|----------------------------------|-----------------|
| `module:make-cast` | `src/Casts` | `--inbound` |
| `module:make-channel` | `src/Broadcasting` | |
| `module:make-command` | `src/Console/Commands` | `--command=` |
| `module:make-component` | `src/View/Components` + `resources/views/components` | `--inline`, `--view` |
| `module:make-controller` | `src/Http/Controllers` | `--api`, `--invokable`, `--model=`, `--parent=`, `--resource`, `--requests`, `--singleton`, `--creatable` |
| `module:make-event` | `src/Events` | |
| `module:make-exception` | `src/Exceptions` | `--render`, `--report` |
| `module:make-factory` | `database/factories` | `--model=` |
| `module:make-job` | `src/Jobs` | `--sync` |
| `module:make-listener` | `src/Listeners` | `--event=`, `--queued` |
| `module:make-mail` | `src/Mail` | `--markdown=` |
| `module:make-middleware` | `src/Http/Middleware` | |
| `module:make-migration` | `database/migrations` | `--create=`, `--table=`, `--path=` |
| `module:make-model` | `src/Models/<Name>/<Name>.php` | `--all`, `--migration`, `--factory`, `--seed`, `--controller`, `--resource`, `--api`, `--requests`, `--policy`, `--pivot`, `--morph-pivot`, `--traits`, `--test`, `--pest` |
| `module:make-notification` | `src/Notifications` | `--markdown=` |
| `module:make-observer` | `src/Observers` | `--model=` |
| `module:make-policy` | `src/Policies` | `--model=`, `--guard=` |
| `module:make-provider` | `src/Providers` | |
| `module:make-request` | `src/Http/Requests` | |
| `module:make-resource` | `src/Http/Resources` | `--collection` |
| `module:make-rule` | `src/Rules` | `--implicit` |
| `module:make-scope` | `src/Models/Scopes` | |
| `module:make-seeder` | `database/seeders` | |
| `module:make-test` | `tests/Feature` (or `tests/Unit`) | `--unit`, `--pest` |
| `module:make-trait` | `src/Traits` (or next to a model) | `--model=` |
| `module:make-view` | `resources/views` | `--extension=`, `--test`, `--pest` |

Most generators also accept `--force` to overwrite an existing file.

### Seeding

| Command | Description |
|---------|-------------|
| `module:seed {module}` | Run a single module's `DatabaseSeeder`. Use `--force` in production. |
| `module:seed-list` | List the registered module seeders in execution order. `--json` for machine-readable output. |
| `module:seed-check` | Check the registered seeders for circular or missing dependencies. |

### Inspection cache

| Command | Description |
|---------|-------------|
| `module:cache` | Warm the cache used by `module:list`, `module:analyze` and `module:cache-stats`. |
| `module:cache-clear {module?}` | Clear that cache for one or all modules. |
| `module:cache-stats` | Show what is currently cached. |

These commands only cache the *metadata the package's own commands display* (module list, config, migration and view
file lists). They do not change how modules are loaded at runtime. For production keep using Laravel's own
`config:cache`, `route:cache`, `view:cache` and `optimize`. If `module:list` does not show a module you just created,
run `module:cache-clear`.

### Other

| Command | Description |
|---------|-------------|
| `module:extract-translation {module}` | Scan the module's `src` and views for translation strings and add the missing ones to its `lang/*.json` files. `--create` creates missing language files. |
| `app:install` | Project bootstrap helper: creates `.env` if missing, then runs `migrate:fresh`, `db:seed`, `vendor:publish --all`, `storage:link` and `composer dump-autoload`. **Destructive** (it drops all tables); it asks for confirmation in production. |

## Service providers

Each module has four providers in `modules/<Module>/src/Providers`. `AppServiceProvider` is the only one registered
with Laravel (through composer auto-discovery); it registers the other three through its `$providers` array.

### AppServiceProvider

Register things by filling in the arrays; no boilerplate needed.

```php
namespace Modules\Blog\Providers;

use ErlandMuchasaj\Modules\Providers\BaseAppServiceProvider;

class AppServiceProvider extends BaseAppServiceProvider
{
    protected string $module = 'Blog';

    // Additional service providers of this module.
    protected array $providers = [
        RouteServiceProvider::class,
        EventServiceProvider::class,
        SeedServiceProvider::class,
        // NewServiceProvider::class,
    ];

    // Model => Policy
    protected array $policies = [
        Post::class => PostPolicy::class,
    ];

    // Model => Observer
    protected array $observers = [
        Post::class => PostObserver::class,
    ];

    // Class aliases (alias => class)
    protected array $aliases = [
        'BlogHelper' => \Modules\Blog\Utils\BlogHelper::class,
    ];

    // Global middleware
    protected array $middleware = [
        AddXHeader::class,
    ];

    // Middleware appended to existing (or new) groups
    protected array $middlewareGroups = [
        'web' => [
            RememberLocale::class,
        ],
        'api' => [
            IdempotencyMiddleware::class,
        ],
    ];

    // Route middleware aliases
    protected array $routeMiddleware = [
        'ip_whitelist' => IpWhitelist::class,
    ];

    // Artisan commands
    protected array $commands = [
        SendAnnouncementNotifications::class,
    ];
}
```

For anything that does not fit an array, override one of the empty hook methods instead of `boot()`/`register()`:

| Hook | Called during | Use it for |
|------|---------------|------------|
| `registerBindings()` | `register` | Container bindings and singletons. |
| `registerFacades()` | `register` | Facade bindings. |
| `bootValidators()` | `boot` | Custom validation rules. |
| `bootBladeDirective()` | `boot` | Blade directives. |
| `bootServices()` | `boot` | Anything else (view composers, macros, `Livewire::component()`, ...). |

```php
protected function registerBindings(): void
{
    $this->app->singleton(PostRepository::class, EloquentPostRepository::class);
}
```

If you do override `boot()` or `register()`, call the parent method.

The base provider also takes care of loading the module's migrations, views, Blade components, translations and config,
and of registering the publishable resources described [below](#publishing-module-resources).

### EventServiceProvider

A regular Laravel event service provider (`modules/<Module>/src/Providers/EventServiceProvider.php`):

```php
protected $listen = [
    PostPublished::class => [
        NotifySubscribers::class,
    ],
];

protected $subscribe = [
    PostEventSubscriber::class,
];
```

### RouteServiceProvider and SeedServiceProvider

See [Routes](#routes) and [Seeders](#seeders).

## Routes

`modules/<Module>/routes/web.php`, `api.php` and `channels.php` are loaded by the module's `RouteServiceProvider`:

| File | Middleware | URL prefix | Route name prefix |
|------|------------|------------|-------------------|
| `web.php` | `web` | none | none |
| `api.php` | `api` | `api` | `api.` |
| `channels.php` | - | - | - |

Change the defaults by overriding the properties in the module's `RouteServiceProvider`:

```php
class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\\Blog\\Http\\Controllers';

    protected string $apiPrefix = 'api/v1';        // default: 'api'
    protected string $apiNamePrefix = 'api.v1';    // default: 'api'
    protected array $webMiddleware = ['web', 'auth'];        // default: ['web']
    protected array $apiMiddleware = ['api', 'auth:sanctum']; // default: ['api']

    // getWebRoute(), getApiRoute() and getChannelsRoute() return the route file paths
}
```

Reference controllers by class in your route files:

```php
use Modules\Blog\Http\Controllers\PostController;

Route::resource('posts', PostController::class);
```

A route file that does not exist is skipped, so you can delete the ones you do not need.

## Views, components, translations and config

Everything is namespaced with the module alias (the snake_case module name).

```php
// Views: modules/Blog/resources/views/pages/index.blade.php
return view('blog::pages.index');

// Translations: modules/Blog/lang/en/messages.php
__('blog::messages.welcome');

// JSON translations: modules/Blog/lang/en.json
__('Read more');

// Config: modules/Blog/config/config.php
config('modules.blog.config.posts_per_page');
```

```blade
{{-- Class based component: modules/Blog/src/View/Components/Alert.php --}}
{{-- Anonymous component:   modules/Blog/resources/views/components/alert.blade.php --}}
<x-blog::alert type="success" />

@include('blog::partials.comment-form')
```

## Database

### Migrations

```shell
php artisan module:make-migration Blog create_posts_table
php artisan migrate
```

Module migrations live in `modules/<Module>/database/migrations` and are loaded automatically, so `migrate`,
`migrate:rollback`, `migrate:fresh`, etc. include them.

### Models and factories

Each model gets its own folder, so its traits, scopes and other helpers can live next to it:

```shell
php artisan module:make-model Blog Post --factory
```

```text
modules/Blog/src/Models/Post/Post.php                   => Modules\Blog\Models\Post\Post
modules/Blog/database/factories/Post/PostFactory.php    => Modules\Blog\Database\Factories\Post\PostFactory
```

Laravel's factory discovery only looks in `Database\Factories` of the application, so the model has to point to its
module factory. `module:make-model --factory` (or `--all`) writes this for you; add it yourself to models created
without the flag:

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

A factory generated on its own follows the same layout: the file is placed in the folder that matches its namespace,
so `Post` and `Post/PostFactory` both produce `database/factories/Post/PostFactory.php`:

```shell
php artisan module:make-factory Blog Post --model=Post
```

### Seeders

Every module has a `DatabaseSeeder` (`modules/<Module>/database/seeders/DatabaseSeeder.php`). Call your other seeders
from it, exactly as in a Laravel application:

```shell
php artisan module:make-seeder Blog PostSeeder
```

```php
namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            PostSeeder::class,
        ]);
    }
}
```

Module seeders run automatically, **after** your application's own `DatabaseSeeder`, when you run one of these from the
command line:

```shell
php artisan db:seed
php artisan migrate:fresh --seed
php artisan migrate:refresh --seed
```

They are skipped when you ask for a specific seeder (`db:seed --class=...`, `migrate:fresh --seeder=...`). To seed a
single module use `php artisan module:seed Blog`.

The order in which module seeders run is controlled in each module's `SeedServiceProvider`:

```php
namespace Modules\Shop\Providers;

use ErlandMuchasaj\Modules\Providers\BaseSeedServiceProvider;

class SeedServiceProvider extends BaseSeedServiceProvider
{
    // The seeder to run for this module. Set it to '' to opt out of automatic seeding.
    protected string $namespace = 'Modules\\Shop\\Database\\Seeders\\DatabaseSeeder';

    // Higher numbers run first. Default: 100.
    protected int $priority = 100;

    // Seeder classes that must run before this one.
    protected array $dependencies = [
        'Modules\\Blog\\Database\\Seeders\\DatabaseSeeder',
    ];
}
```

Dependencies always win over priority. Use `module:seed-list` to see the resulting order and `module:seed-check` to
detect circular or missing dependencies.

## Publishing module resources

A module's resources can be published into the application, e.g. to override its views. Always pass the module's
provider, because all modules share the same tag names:

```shell
php artisan vendor:publish --provider="Modules\Blog\Providers\AppServiceProvider" --tag=views
```

| Tag | From (`modules/Blog/`) | To |
|-----|------------------------|----|
| `config` | `config/config.php` | `config/modules/blog/config.php` |
| `migrations` | `database/migrations` | `database/migrations` |
| `views` | `resources/views` | `resources/views/vendor/modules/blog` |
| `view-components` | `src/View/Components`, `resources/views/components` | `app/View/Components`, `resources/views/components` |
| `assets` | `resources/assets` | `public/blog` |
| `lang` | `lang` | `lang/vendor/blog` |
| `factories` | `database/factories` | `database/factories` |
| `seeders` | `database/seeders/DatabaseSeeder.php` | `database/seeders/BlogModuleSeeder.php` |

## Customizing stubs

The generators look for a stub in your application's `stubs/` directory before falling back to the bundled one, the
same way Laravel's `make:*` commands do. To customize the output of `module:make-controller`, for example, create
`stubs/controller.stub` (or `stubs/controller.api.stub`, `stubs/controller.model.stub`, ...) in your application.
The bundled stubs are in
[`src/Console/Commands/stubs`](src/Console/Commands/stubs).

## Testing modules

`module:make-test Blog PostTest` creates `modules/Blog/tests/Feature/PostTest.php` in the
`Modules\Blog\Tests\Feature` namespace (`--unit` for `tests/Unit`, `--pest` for a Pest test).

Add the module test folders to your application's `phpunit.xml` so they run with your test suite:

```xml
<testsuite name="Modules">
    <directory>modules/*/tests</directory>
</testsuite>
```

Generated tests extend your application's `Tests\TestCase`. Composer only loads `autoload-dev` of the root package,
so if a module has test helpers of its own (e.g. `Modules\Blog\Tests\TestCase`), register that namespace in your
application's `composer.json` and run `composer dump-autoload`:

```json
"autoload-dev": {
    "psr-4": {
        "Tests\\": "tests/",
        "Modules\\Blog\\Tests\\": "modules/Blog/tests/"
    }
}
```

## Configuration

After publishing, `config/modules.php` contains:

| Key | Default | Description |
|-----|---------|-------------|
| `namespace` | `Modules` | Root namespace of generated modules. |
| `folder` | `modules` | Folder (relative to the application root) where modules live. |

Both are honoured by all generators, by the base providers and by the Filament plugin. Choose them before creating
your first module: existing modules are not rewritten when you change them.

Configs published by earlier versions also contain `paths`, `seeds`, `discovery`, `cache` and `composer` sections.
The package never read them; you can delete them.

## Removing a module

```shell
php artisan module:remove Blog
```

This deletes `modules/Blog`, removes `modules/blog` from your `composer.json` and runs `composer remove`. Migrations
that were already run are **not** rolled back; roll them back first if you need to.

## Livewire, Filament and Alpine.js

All three are optional. Nothing is loaded, and nothing changes, unless the package is installed.

### Livewire (3.x and 4.x)

When Livewire is installed, components placed in `./modules/ModuleName/src/Livewire` are registered
automatically under the module alias (the snake_case module name, the same one used for views and translations):

```php
// modules/Blog/src/Livewire/Counter.php            => Modules\Blog\Livewire\Counter
// modules/Blog/src/Livewire/Forms/CreatePost.php   => Modules\Blog\Livewire\Forms\CreatePost
```

```blade
<livewire:blog::counter />
<livewire:blog::forms.create-post />
@livewire('blog::counter')
```

Component views live in the module as usual, e.g. `return view('blog::livewire.counter');`.
On Livewire 4, single-file and multi-file components in `./modules/ModuleName/resources/views/livewire`
are registered under the same alias as well.

Components you register yourself in `bootServices()` (e.g. `Livewire::component(...)`) keep working and take precedence.
To opt a module out, override `bootLivewireComponents()` in its `AppServiceProvider` with an empty body.

### Filament (3.x, 4.x and 5.x)

Add the plugin to a panel to discover the resources, pages, widgets and clusters modules ship under
`./modules/ModuleName/src/Filament/{Resources,Pages,Widgets,Clusters}`:

```php
use ErlandMuchasaj\Modules\Filament\ModulesPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(ModulesPlugin::make());
        // ->plugin(ModulesPlugin::make()->only(['Blog', 'Shop']))
        // ->plugin(ModulesPlugin::make()->except(['Legacy']))
        // ->plugin(ModulesPlugin::make()->directory('Filament/Admin')) // one folder per panel
}
```

### Alpine.js

Alpine.js is a front-end library, so the package has nothing to register for it. Livewire already bundles Alpine:
when a page uses Livewire, do not load a second copy of Alpine from a module's assets. Register module specific
Alpine components inside the `alpine:init` event instead.

## AI assistance with Laravel Boost

This package ships an AI guideline and an agent skill for [Laravel Boost](https://laravel.com/docs/boost), so AI
coding agents (Claude Code, Cursor, GitHub Copilot, Codex, Junie, ...) know how modules are structured and which
`module:*` command to run instead of guessing.

| File | Purpose |
|------|---------|
| [`resources/boost/guidelines/core.blade.php`](resources/boost/guidelines/core.blade.php) | A short, always-loaded guideline: what the package is and when to activate the skill. |
| [`resources/boost/skills/laravel-modules-development/SKILL.md`](resources/boost/skills/laravel-modules-development/SKILL.md) | The `laravel-modules-development` skill: structure, naming, commands, providers, routes, views, seeding and common pitfalls. Loaded on demand. |

### Using it in your application

1. Install Boost (once per project):

    ```shell
    composer require laravel/boost --dev
    php artisan boost:install
    ```

2. During `boost:install`, Boost detects `erlandmuchasaj/laravel-modules` among your installed packages, adds its
   guideline to your agent's instruction file (`CLAUDE.md`, `AGENTS.md`, ...) and offers to install the
   `laravel-modules-development` skill into your agent's skills folder (for example `.claude/skills/`).

3. After installing or updating this package in a project that already uses Boost, refresh the generated files:

    ```shell
    php artisan boost:update
    ```

Your agent then activates the skill on its own whenever a task touches a module, for example
*"add a Comment model with a factory and a policy to the Blog module"*. You can also ask for it explicitly:
*"use the laravel-modules-development skill"*.

Not using Boost? Point your agent at
`vendor/erlandmuchasaj/laravel-modules/resources/boost/skills/laravel-modules-development/SKILL.md`, or copy that
folder into your agent's skills directory.

---
## Support me

I invest a lot of time and resources into creating [best in class open source packages](https://github.com/erlandmuchasaj?tab=repositories).

If you found this package helpful you can show support by clicking on the following button below and donating some amount to help me work on these projects frequently.

<a href="https://www.buymeacoffee.com/erland" target="_blank">
    <img src="https://www.buymeacoffee.com/assets/img/guidelines/download-assets-2.svg" style="height: 45px; border-radius: 12px" alt="buy me a coffee"/>
</a>

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please see [SECURITY](SECURITY.md) for details.

## Credits

- [Erland Muchasaj](https://github.com/erlandmuchasaj)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
