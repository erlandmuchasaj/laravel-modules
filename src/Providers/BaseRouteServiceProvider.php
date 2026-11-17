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

    /**
     * Per-instance cache for file_exists results.
     * Instance property (not static) so Octane workers see a fresh state per boot.
     */
    private array $routeFileCache = [];

    abstract protected function getWebRoute(): string;

    abstract protected function getApiRoute(): string;

    abstract protected function getChannelsRoute(): string;

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        parent::boot();

        // Broadcast channels are not part of the route cache, so they are loaded
        // on every boot instead of in map(), which is skipped under route:cache.
        $this->loadChannelsRoutes();
    }

    /**
     * Define the routes for the application.
     */
    public function map(Router $router): void
    {
        // The parent provider only calls map() when the routes are not cached.
        $this->mapApiRoutes();

        $this->mapWebRoutes();
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
     * Check if the route file exists with per-instance caching.
     * Uses an instance property (not static) so Octane/Swoole workers can
     * get fresh results across requests when the provider is re-instantiated.
     */
    protected function routeFileExists(string $path): bool
    {
        if (!isset($this->routeFileCache[$path])) {
            $this->routeFileCache[$path] = $path && file_exists($path);
        }

        return $this->routeFileCache[$path];
    }

        /**
     * Check if routes are cached.
     */
    protected function routesAreCached(): bool
    {
        return $this->app instanceof CachesRoutes && $this->app->routesAreCached();
    }

}
