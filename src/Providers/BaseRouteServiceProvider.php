<?php

namespace ErlandMuchasaj\Modules\Providers;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;

abstract class BaseRouteServiceProvider extends ServiceProvider
{
    /**
     * The root namespace to assume when generating URLs to actions.
     *
     * @var string|null
     */
    protected $namespace = '';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        parent::boot();
    }

    abstract protected function getWebRoute(): string;

    abstract protected function getApiRoute(): string;

    abstract protected function getChannelsRoute(): string;

    /**
     * Define the routes for the application.
     */
    public function map(Router $router): void
    {
        // If the routes have not been cached, we will include them in a route group
        // so that all the routes will be conveniently registered to the given
        // controller namespace. After that we will load the EMCMS routes file.

        if (! ($this->app instanceof CachesRoutes && $this->app->routesAreCached())) {
            // mapApiRoutes
            $router->group([
                'prefix' => 'api',
                'middleware' => ['api'],
                'namespace' => $this->namespace,
            ], function (Router $router) {
                $this->loadApiRoutes($router);
            });

            // mapWebRoutes
            $router->group([
                'middleware' => ['web'],
                'namespace' => $this->namespace,
            ], function (Router $router) {
                $this->loadWebRoutes($router);
            });
        }

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
                require $frontend;
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
                'as' => 'api.',
            ], function () use ($api) {
                require $api;
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
            require $channels;
        }
    }
}
