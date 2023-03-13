<?php

namespace ErlandMuchasaj\Modules\Providers;

use ErlandMuchasaj\Modules\Console\Commands;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    protected bool $defer = true;

    /**
     * The available commands
     *
     * @var array<int, class-string>
     */
    protected array $commands = [
        Commands\AppVersion::class,
        Commands\AppInstall::class,
        Commands\ListCommand::class,
        Commands\CastMakeCommand::class,
        Commands\ScopeMakeCommand::class,
        Commands\ChannelMakeCommand::class,
        Commands\ConsoleMakeCommand::class,
        Commands\ComponentMakeCommand::class,
        Commands\EventMakeCommand::class,
        Commands\ExceptionMakeCommand::class,
        Commands\JobMakeCommand::class,
        Commands\ListenerMakeCommand::class,
        Commands\ControllerMakeCommand::class,
        Commands\ModelMakeCommand::class,
        Commands\NotificationMakeCommand::class,
        Commands\ObserverMakeCommand::class,
        Commands\PolicyMakeCommand::class,
        Commands\ProviderMakeCommand::class,
        Commands\RequestMakeCommand::class,
        Commands\ResourceMakeCommand::class,
        Commands\RuleMakeCommand::class,
        Commands\TraitMakeCommand::class,
        Commands\TestMakeCommand::class,
        Commands\MigrateMakeCommand::class,
        Commands\MiddlewareMakeCommand::class,
        Commands\MailMakeCommand::class,
        Commands\SeederMakeCommand::class,
        Commands\FactoryMakeCommand::class,
        Commands\ModuleMakeCommand::class,
    ];

    /**
     * Register the commands.
     */
    public function register(): void
    {
        $this->registerMigrator();

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return $this->commands;
    }

    /**
     * Register the migrator service.
     */
    protected function registerMigrator(): void
    {
        $this->app->when(MigrationCreator::class)
            ->needs('$customStubPath')
            ->give(function ($app) {
                return $app->basePath('stubs');
            });
    }
}
