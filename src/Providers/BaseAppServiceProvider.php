<?php
declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Providers;

use ErlandMuchasaj\Modules\Traits\CanPublishConfiguration;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Base service provider for modules.
 * Extend this to create module-specific providers.
 */
abstract class BaseAppServiceProvider extends ServiceProvider
{
    use CanPublishConfiguration;

    /**
     * The Module Name
     * @var string
     */
    protected string $module;

    /**
     * Indicates if loading of the provider is deferred.
     * @var bool
     */
    protected bool $defer = false;

    /**
     * Get the services provided by the provider.
     *
     * @example
     * RouteServiceProvider::class,
     * EventServiceProvider::class,
     * SeedServiceProvider::class,
     * @example
     *
     * @var array<int, class-string>
     */
    protected array $providers = [];

    /**
     * The policy mappings for the application.
     *
     * @example Model::class => ModelPolicy::class
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [];

    /**
     * Boot module observers.
     *
     * @example Model::class => ModelObserver::class
     *
     * @var array<class-string, class-string>
     */
    protected array $observers = [
    ];

    /**
     * register module aliases.
     *
     * @example 'alias' => Model::class
     *
     * @var array<non-empty-string, class-string>
     */
    protected array $aliases = [
    ];

    /**
     * The application's global middleware stack.
     *
     * @example MiddlewareClass::class
     *
     * @var array<int, class-string>
     */
    protected array $middleware = [
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string>>
     */
    protected array $middlewareGroups = [
        'web' => [
        ],
        'api' => [
        ],
    ];

    /**
     * The application's route middleware.
     * This middleware may be assigned to group or used individually.
     *
     * @example
     * 'subscription.is_customer' => hasBeenCustomer::class,
     *
     * @var array<non-empty-string, class-string>
     */
    protected array $routeMiddleware = [
    ];

    /**
     * The available command shortname.
     *
     * @example CommandNameClass::class
     *
     * @var array<int, class-string>
     */
    protected array $commands = [
    ];

    /**
     * Bootstrap your package's services.
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // Teach Laravel's HasFactory resolver to handle the per-model subdirectory
        // convention used by this package.
        //
        // Convention: Modules\{Name}\Models\{Sub}\{Class}
        //           → Modules\{Name}\Database\Factories\{Sub}\{Class}Factory
        //
        // All module service providers inherit this base class and call boot().
        // Registering the same closure multiple times is safe — the last writing wins,
        // but every writing is identical logic.
        /**
         * @note: does not work on this PHP version
         */
        // Model::resolveFactoryNamesUsing(static function (string $modelClass): string {
        //     if (str_contains($modelClass, '\\Models\\')) {
        //         return str_replace('\\Models\\', '\\Database\\Factories\\', $modelClass).'Factory';
        //     }
        //
        //     // Standard App\Models\Foo → Database\Factories\FooFactory fallback
        //     return str_replace(['App\\Models\\', 'App\\'], ['Database\\Factories\\', 'Database\\Factories\\'], $modelClass).'Factory';
        // });

        // $this->app->booted(function () {
        //      # do something after boot for example configure a command to run and register it in schedule runner
        //     /** @var Schedule */
        //     $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
        //     $schedule->command('modules:check')
        //         ->everyMinute()
        //         ->withoutOverlapping()
        //         ->sendOutputTo(storage_path('logs/laravel-modules.log'), true)
        //         ->when(config('modules.scheduling.enabled'));
        // });

        /**
         * @todo we can also separate boot and register config on boot and register methods.
         */
        // $this->bootConfig($this->module(true), 'config');
        // $this->registerConfig($this->module(true), 'config');

        // publish migrations
        $this->bootMigrations();

        // boot Factories
        $this->bootFactories();

        // bootSeeders
        $this->bootSeeders();

        // boot translations
        $this->bootTranslations();

        // boot Views
        $this->bootViews();




        // boot middleware
        $this->bootMiddleware();

        // boot observers
        $this->bootObservers();

        // boot Policies
        $this->bootPolicies();

        // boot Validators
        $this->bootValidators();

        // boot Blade directive and components
        $this->bootBladeDirective();

        // boot Services
        $this->bootServices();
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        // Publish configs
        $this->publishConfig($this->module(true), 'config');

        // Register Bindings
        $this->registerBindings();

        // Register Facades
        $this->registerFacades();

        // Register Aliases
        $this->registerAliases();

        // Register providers
        $this->registerProviders();

        // Register Commands
        $this->registerCommands();
    }

    /**
     * registerBindings
     */
    protected function registerBindings(): void
    {
        // $this->app->bind(
        //     CoreRepositoryInterface::class,
        //     CoreEloquentRepository::class
        // );
    }

    /**
     * Register Facades.
     */
    protected function registerFacades(): void
    {
        // $loader = AliasLoader::getInstance();
        // $loader->alias('core', CoreFacade::class);

        // $this->app->singleton('core', function () {
        //     return app()->make(Core::class);
        // });

        // $this->app->scoped('core', function () {
        //     return app()->make(Core::class);
        // });
    }

    /**
     * Register Aliases.
     */
    protected function registerAliases(): void
    {
        if (empty($this->aliases)) {
            return;
        }

        $loader = AliasLoader::getInstance();
        foreach ($this->aliases as $aliasName => $aliasClass) {
            $loader->alias($aliasName, $aliasClass);
        }
    }

    /**
     * registerCommands
     */
    protected function registerCommands(): void
    {
        if (!$this->app->runningInConsole() || empty($this->commands)) {
            return;
        }

        $this->commands($this->commands);
    }

    /**
     * registerProviders
     */
    protected function registerProviders(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * bootMiddleware
     *
     * @throws BindingResolutionException
     */
    protected function bootMiddleware(): void
    {
        // Skip the booted() closure entirely when nothing is registered
        $hasMiddleware = ! empty($this->middleware)
            || ! empty($this->routeMiddleware)
            || ! empty(array_filter(array_map('array_filter', $this->middlewareGroups)));
        if (! $hasMiddleware) {
            return;
        }

        $this->app->booted(function ($app) {
            // Register global middleware
            $kernel = $app->make(Kernel::class);
            $router = $app->make(Router::class);

            // Register global middleware
            foreach ($this->middleware as $middleware) {
                $kernel->pushMiddleware($middleware);
            }

            // Register route middleware
            foreach ($this->routeMiddleware as $name => $class) {
                $router->aliasMiddleware($name, $class);
                // $this->app['router']->aliasMiddleware($name, $class);
            }

            // Register group middleware
            foreach ($this->middlewareGroups as $group => $middlewares) {
                foreach ($middlewares as $middleware) {
                    $router->pushMiddlewareToGroup($group, $middleware);
                    // $this->app['router']->pushMiddlewareToGroup($group, $middleware);
                }
            }
        });
    }

    /**
     * bootValidators
     */
    protected function bootValidators(): void
    {
        // overwrite this method if you need to add custom validation rules
    }

    /**
     * bootBladeDirective
     */
    protected function bootBladeDirective(): void
    {
        // Override in subclasses to register custom Blade directives and components.
    }

    /**
     * bootPolicies
     */
    protected function bootPolicies(): void
    {
        if (empty($this->policies)) {
            return;
        }

        // Gate::policy(Model::class, ModelPolicy::class);
        // Ex: Gate::policy(User::class, UserPolicy::class);
        foreach ($this->policies as $className => $policyName) {
            Gate::policy($className, $policyName);
        }
    }

    /**
     * bootObservers
     */
    protected function bootObservers(): void
    {
        if (empty($this->observers)) {
            return;
        }

        foreach ($this->observers as $className => $observerName) {
            // $classObj = app($className);
            // if (! is_null($classObj)) {
            //     $classObj::observe($observerName);
            // }

            $className::observe($observerName);
        }
    }

    /**
     * bootServices
     */
    protected function bootServices(): void
    {
        // Boot your services here
    }

    /**
     * boot migrations.
     */
    protected function bootMigrations(): void
    {
        $path = base_path($this->base.DIRECTORY_SEPARATOR.$this->module().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations');

        if (!is_dir($path)) {
            return;
        }

        $this->loadMigrationsFrom($path);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $path => database_path('migrations'),
            ], 'migrations');
        }
    }

    /**
     * Register views & Publish views.
     * This function registers views, components, and assets.
     *
     * @throws BindingResolutionException
     */
    protected function bootViews(): void
    {

        $basePath = base_path($this->base.DIRECTORY_SEPARATOR.$this->module().DIRECTORY_SEPARATOR);

        $viewPath = $basePath.'resources'.DIRECTORY_SEPARATOR.'views';

        $assetsPath = $basePath.'resources'.DIRECTORY_SEPARATOR.'assets';

        $componentPath = $basePath.'src'.DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR.'Components';

        if (is_dir($componentPath)) {
            // This will allow the usage of package components by their vendor namespace using the package-name:: syntax.
            // ex: <x-core::calendar /> <x-core::alert /> <x-core::forms.input /> # for subdirectories.
            Blade::componentNamespace('\\Modules\\'.$this->module().'\\View\\Components', $this->module(true));
        }

        $this->loadViewsFrom($viewPath, $this->module(true));

        if ($this->app->runningInConsole()) {
            // Publish views
            $this->publishes([
                $viewPath => resource_path("views/vendor/$this->base/{$this->module(true)}"),
            ], 'views');

            // Publish view components
            $this->publishes([
                $componentPath => app_path('View/Components'),
                $viewPath.DIRECTORY_SEPARATOR.'components' => resource_path('views/components'),
            ], 'view-components');

            // Publish assets
            $this->publishes([
                $assetsPath => public_path($this->module(true)),
            ], 'assets');
            // if you want to access the assets use module/js or module.css
            // <script src="{{ asset('{{module}}/js/app.js') }}"></script>
            // <link href="{{ asset('{{module}}/css/app.css') }}" rel="stylesheet" />
        }
    }

    /**
     * Register & Publish translations.
     *
     * Package translations are referenced using the module::file.line syntax convention
     * So, you may load the user module's welcome line from the message file like so:
     * echo trans('user::messages.welcome');
     */
    protected function bootTranslations(): void
    {
        static $isV9Plus = null;
        $isV9Plus ??= version_compare(app()->version(), '9.0.0') >= 0;

        // there is a change in structure for translations from v8 to v9.
        if ($isV9Plus) {
            $path = base_path($this->base.DIRECTORY_SEPARATOR.$this->module().DIRECTORY_SEPARATOR.'lang');
        } else {
            $path = base_path($this->base.DIRECTORY_SEPARATOR.$this->module().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'lang');
        }

        if (!is_dir($path)) {
            return;
        }

        // to read language: module::file.key
        // ex: __('core::messages.welcome');
        $this->loadTranslationsFrom($path, $this->module(true));

        // __('Normal Text');
        $this->loadJsonTranslationsFrom($path);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $path => lang_path("vendor/{$this->module(true)}"),
            ], 'lang');
        }
    }

    /**
     * Publish module factories to the application database/factories directory.
     */
    protected function bootFactories(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $factoriesPath = base_path(
            $this->base.DIRECTORY_SEPARATOR.$this->module().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'factories'
        );

        if (is_dir($factoriesPath)) {
            $this->publishes([
                $factoriesPath => database_path('factories'),
            ], 'factories');
        }
    }

    protected function bootSeeders(): void
    {
        if ($this->app->runningInConsole()) {
            $seederPath = $this->modulePath('database/seeders/DatabaseSeeder.php');

            if (! is_file($seederPath)) { return; }
            $this->publishes([
                $seederPath => database_path('seeders/'.$this->module().'ModuleSeeder.php')
            ], 'seeders');
        }
    }

    /**
     * Get a module case according to different usage cases.
     * Studly or snake case.
     */
    protected function module(bool $snake = false): string
    {
        if ($snake === true) {
            return Str::snake($this->module); # module_name
        }

        return Str::studly($this->module); # ModuleName
    }

    /**
     * Get a module base path.
     */
    protected function modulePath(string $path = ''): string
    {
        $basePath = base_path($this->base . '/' . $this->module());

        return $path ? $basePath . '/' . $path : $basePath;
    }

}
