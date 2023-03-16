<?php

namespace ErlandMuchasaj\Modules;

use ErlandMuchasaj\Modules\Providers\ConsoleServiceProvider;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Package name.
     */
    public static string $abstract = 'modules';

    /**
     * Booting the package.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path(static::$abstract.'.php'),
            ], 'config');
        }
    }

    /**
     * Register all modules.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/config.php',
            static::$abstract
        );

        $this->app->register(ConsoleServiceProvider::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['modules'];
    }
}
