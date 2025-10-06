<?php

namespace ErlandMuchasaj\Modules\Providers;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

abstract class BaseRouteServiceProvider extends ServiceProvider
{
    /**
     * The controller namespace for the module.
     * 
     * The root namespace to assume when generating URLs to actions.
     * This is used by each module and should not overwrite the base service model
     * @readonly this was renamed from $namespaced => $moduleNamespace
     * to avoid overlying name conflicts.
     */
    protected string $moduleNamespace = '';

    /**
     * API route prefix.
     */
    protected string $apiPrefix = 'api';

    /**
     * API route name prefix.
     */
    protected string $apiNamePrefix = 'api';

    /**
     * Web route middleware group.
     */
    protected array $webMiddleware = ['web'];

    /**
     * API route middleware group.
     */
    protected array $apiMiddleware = ['api'];

    abstract protected function getWebRoute(): string;

    abstract protected function getApiRoute(): string;

    abstract protected function getChannelsRoute(): string;

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(Router $router): void
    {
        // If the routes have not been cached, we will include them in a route group
        // so that all the routes will be conveniently registered to the given
        // controller namespace. After that we will load the EMCMS routes file.

        // Skip if routes are cached
        if ($this->routesAreCached()) {
            return;
        }

        // mapApiRoutes
        $this->mapApiRoutes();

        // mapWebRoutes
        $this->mapWebRoutes();

        // Channels
        $this->loadChannelsRoutes();
    }

   /**
     * Define the "api" routes for the module.
     */
    protected function mapApiRoutes(): void
    {
        $route = $this->getApiRoute();

        if (!$this->routeFileExists($route)) {
            return;
        }

        Route::prefix($this->apiPrefix)
            ->as("{$this->apiNamePrefix}.")
            ->middleware($this->apiMiddleware)
            ->namespace($this->moduleNamespace . '\\Api')
            ->group($route);
    }

    /**
     * Define the "web" routes for the module.
     */
    protected function mapWebRoutes(): void
    {
        $route = $this->getWebRoute();

        if (!$this->routeFileExists($route)) {
            return;
        }

        Route::middleware($this->webMiddleware)
            ->namespace($this->moduleNamespace)
            ->group($route);
    }

    /**
     * Load BroadCast Channel routes
     */
    private function loadChannelsRoutes(): void
    {

        $route = $this->getChannelsRoute();

        if (!$this->routeFileExists($route)) {
            return;
        }

        require $route;
    }

    /**
     * Check if route file exists with caching.
     */
    protected function routeFileExists(string $path): bool
    {
        // Cache the file existence check to avoid filesystem hits
        static $cache = [];

        if (!isset($cache[$path])) {
            $cache[$path] = $path && file_exists($path);
        }

        return $cache[$path];
    }

        /**
     * Check if routes are cached.
     */
    protected function routesAreCached(): bool
    {
        return $this->app instanceof CachesRoutes && $this->app->routesAreCached();
    }

}
