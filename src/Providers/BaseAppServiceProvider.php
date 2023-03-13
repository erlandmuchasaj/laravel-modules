<?php

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

abstract class BaseAppServiceProvider extends ServiceProvider
{
    use CanPublishConfiguration;

    /**
     * The Module Name
     */
    protected string $module;

    /**
     * Indicates if loading of the provider is deferred.
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
     * @return array<int, class-string>
     */
    protected array $providers = [
    ];

    /**
     * The policy mappings for the application.
     *
     * @example Model::class => ModelPolicy::class
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
    ];

    /**
     * Boot module observers.
     *
     * @example Model::class => ModelObserver::class
     *
     * @return array<class-string, class-string>
     */
    protected array $observers = [
    ];

    /**
     * register module aliases.
     *
     * @example 'alias' => Model::class
     *
     * @return array<string, class-string>
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
     * These middleware may be assigned to group or used individually.
     *
     * @example
     * 'subscription.is_customer' => hasBeenCustomer::class,
     *
     * @var array<string, class-string>
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
        // publish migrations
        $this->bootMigrations();

        // boot translations
        $this->bootTranslations();

        // boot Blade directive and components
        $this->bootBladeDirective();

        // boot Validators
        $this->bootValidators();

        // boot middleware
        $this->bootMiddleware();

        // boot observers
        $this->bootObservers();

        // boot Services
        $this->bootServices();

        // boot Factories
        $this->bootFactories();

        // boot Policies
        $this->bootPolicies();

        // boot Views
        $this->bootViews();
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

        // Register Commands
        $this->registerCommands();

        // Register providers
        $this->registerProviders();
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
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
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
     *
     * @throws BindingResolutionException
     */
    protected function bootMiddleware(): void
    {
        // Register global middleware
        $kernel = $this->app->make(Kernel::class);
        foreach ($this->middleware as $middleware) {
            $kernel->pushMiddleware($middleware);
        }

        $router = $this->app->make(Router::class);

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
        if (app()->environment() === 'testing') {
            logger('This is running tests!');
        }
    }

    /**
     * bootPolicies
     */
    protected function bootPolicies(): void
    {
        // Gate::policy(Model::class, ModelPolicy::class);
        foreach ($this->policies as $className => $policyName) {
            Gate::policy($className, $policyName);
        }
    }

    /**
     * bootObservers
     */
    protected function bootObservers(): void
    {
        // Model::observe(ModelObserver::class);
        foreach ($this->observers as $className => $observerName) {
            $classObj = app($className);
            if (! is_null($classObj)) {
                $classObj::observe($observerName);
            }
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
        $path = base_path('modules'.DIRECTORY_SEPARATOR.$this->module().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations');

        $this->loadMigrationsFrom($path);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $path => database_path('migrations'),
            ], 'migrations');
        }
    }

    /**
     * Register views & Publish views.
     * This function register views, components and assets.
     *
     * @throws BindingResolutionException
     */
    protected function bootViews(): void
    {
        // This will allow the usage of package components by their vendor namespace using the package-name:: syntax.
        // ex: <x-core::calendar /> <x-core::alert /> <x-core::forms.input /> # for sub directories.
        Blade::componentNamespace('\\Modules\\'.$this->module().'\\View\\Components', $this->module(true));

        $basePath = base_path('modules'.DIRECTORY_SEPARATOR.$this->module().DIRECTORY_SEPARATOR);

        $viewPath = $basePath.'resources'.DIRECTORY_SEPARATOR.'views';

        $assetsPath = $basePath.'resources'.DIRECTORY_SEPARATOR.'assets';

        $componentPath = $basePath.'src'.DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR.'Components';

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
     * So, you may load the user module's welcome line from the messages file like so:
     * echo trans('user::messages.welcome');
     */
    protected function bootTranslations(): void
    {
        // there is a change in structure for translations from v8 to v9.
        if (version_compare(app()->version(), '9.0.0') >= 0) {
            $path = base_path('modules'.DIRECTORY_SEPARATOR.$this->module().DIRECTORY_SEPARATOR.'lang');
        } else {
            $path = base_path('modules'.DIRECTORY_SEPARATOR.$this->module().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'lang');
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
     * Register an additional directory of factories.
     */
    protected function bootFactories(): void
    {
        if ($this->app->isLocal()) {
            if ($this->app->runningInConsole()) {
                $this->publishes([
                    __DIR__.'/../../database/seeders/DatabaseSeeder.php' => database_path('seeders/'.$this->module().'ModuleSeeder.php'),
                ], 'seeders');
            }
        }
    }

    /**
     * Get module case according to different usage cases.
     * Studly or snake case.
     */
    protected function module(bool $snake = false): string
    {
        if ($snake === true) {
            return Str::snake($this->module);
        }

        return Str::studly($this->module);
    }
}
