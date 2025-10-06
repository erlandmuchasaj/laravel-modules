<?php

namespace ErlandMuchasaj\Modules;

use ErlandMuchasaj\Modules\Providers\ConsoleServiceProvider;
use ErlandMuchasaj\Modules\Support\SeedOrchestrator;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;

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

            // Setup seeding orchestration
            $this->setupSeedOrchestration();
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

        // Register SeedOrchestrator as singleton
        $this->app->singleton(SeedOrchestrator::class, function () {
            return new SeedOrchestrator();
        });

        $this->app->register(ConsoleServiceProvider::class);
    }


    /**
     * Setup seed orchestration to run after db:seed command.
     */
    protected function setupSeedOrchestration(): void
    {
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            // Only run for seeding commands
            if (!$this->isSeedingCommand($event->command)) {
                return;
            }

            // Only if command was successful and output to console
            if ($event->exitCode !== 0 || !($event->output instanceof ConsoleOutput)) {
                return;
            }

            // Check if we should run module seeds
            if (!$this->shouldRunModuleSeeds($event)) {
                return;
            }

            // Run all registered module seeders in order
            $orchestrator = $this->app->make(SeedOrchestrator::class);
            $orchestrator->run();
        });
    }

    /**
     * Determine if module seeds should run.
     */
    protected function shouldRunModuleSeeds(CommandFinished $event): bool
    {
        $input = $event->input;

        // Don't run if a specific class was provided to db:seed
        if ($event->command === 'db:seed' && method_exists($input, 'getOption')) {
            // Check if --class option is provided and is not the default DatabaseSeeder
            $seeder = $input->getOption('class');
            if ($seeder && $seeder !== 'Database\\Seeders\\DatabaseSeeder') {
                return false;
            }
        }

        // For migrate commands, only run if --seed flag was provided
        if (in_array($event->command, ['migrate:fresh', 'migrate:refresh'])) {
            if (!method_exists($input, 'getOption')) {
                return false;
            }

            // Check if --seed flag is present
            if (!$input->getOption('seed')) {
                return false;
            }

            /**
             * todo: Consider adding an option to skip module seeds if --seeder is provided
             * This would allow users to specify a custom seeder class and skip module seeds
             * when using --seeder option. Uncomment the following lines to enable this behavior.
             */
            if ($input->getOption('seeder')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the command is a seeding command.
     */
    protected function isSeedingCommand(?string $command): bool
    {
        if (!$command) {
            return false;
        }

        $seedingCommands = [
            'db:seed',
            'migrate:fresh',
            'migrate:refresh',
        ];

        return in_array($command, $seedingCommands);
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
