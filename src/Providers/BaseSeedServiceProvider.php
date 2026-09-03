<?php

namespace ErlandMuchasaj\Modules\Providers;

use ErlandMuchasaj\Modules\Support\SeedOrchestrator;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

abstract class BaseSeedServiceProvider extends ServiceProvider
{
    /**
     * The root namespace to assume where to get the seeding data from.
     */
    protected string $namespace = '';

    /**
     * Seeder priority. Higher numbers run first.
     * Default: 100
     * System/Core seeders: 200+
     * Standard modules: 100
     * Optional modules: 50-
     */
    protected int $priority = 100;

    /**
     * Array of seeder class names that must run before this seeder.
     */
    protected array $dependencies = [];

    /**
     * Bootstrap services.
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->registerSeeder();
    }

    /**
     * Register the seeder with the orchestrator.
     *
     * @throws BindingResolutionException
    */
    protected function registerSeeder(): void
    {
        if (empty($this->namespace)) {
            return;
        }

        $orchestrator = $this->app->make(SeedOrchestrator::class);
        $orchestrator->register($this->namespace, $this->priority, $this->dependencies);
    }

}
