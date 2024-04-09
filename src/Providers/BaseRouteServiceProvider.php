<?php

namespace ErlandMuchasaj\Modules\Providers;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;

abstract class BaseRouteServiceProvider extends ServiceProvider
{
    /**
     * The root namespace to assume when generating URLs to actions.
     * This is used by each module and should not overwrite the base service model
     * @readonly this was renamed from $namespaced => $moduleNamespace
     * to avoid overlying name conflicts.
     */
    protected string $moduleNamespace = '';

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

        // mapApiRoutes
        $router->group([
            'prefix' => 'api', // api/route
            'as' => 'api.',    // api.name
            'middleware' => ['api'],
            'namespace' => $this->moduleNamespace,
        ], function (Router $router) {
            $this->loadApiRoutes($router);
        });

        // mapWebRoutes
        $router->group([
            'middleware' => ['web'],
            'namespace' => $this->moduleNamespace,
        ], function (Router $router) {
            $this->loadWebRoutes($router);
        });

        // Channels
        $this->loadChannelsRoutes();
    }

    /**
     * Load all web routes.
     */
    private function loadWebRoutes(Router $router): void
    {
        $frontend = $this->getWebRoute();
        if ($frontend && file_exists($frontend)) {
            $router->group([], function () use ($frontend) {
                $this->loadRoutesFrom($frontend);
            });
        }
    }

    /**
     * Load /Api routes
     */
    private function loadApiRoutes(Router $router): void
    {
        $api = $this->getApiRoute();
        if ($api && file_exists($api)) {
            $router->group([
                'namespace' => 'Api',
            ], function () use ($api) {
                $this->loadRoutesFrom($api);
            });
        }
    }

    /**
     * Load BroadCast Channel routes
     */
    private function loadChannelsRoutes(): void
    {
        $channels = $this->getChannelsRoute();
        if ($channels && file_exists($channels)) {

            if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
                return;
            }

            require $channels;
        }
    }
}
