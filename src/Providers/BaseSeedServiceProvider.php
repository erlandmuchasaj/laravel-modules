<?php

namespace ErlandMuchasaj\Modules\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class BaseSeedServiceProvider extends ServiceProvider
{
    /**
     * The root namespace to assume where to get the seeding data from.
     */
    protected string $namespace = '';

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            if ($this->isConsoleCommandContains(['db:seed', '--seed'], ['--class', 'help', '-h'])) {
                $this->addSeedsAfterConsoleCommandFinished();
            }
        }
    }

    /**
     * Get a value that indicates whether the current command in console
     * contains a string in the specified $fields.
     *
     * @param  string|string[]  $contain_options
     * @param  string|string[]  $exclude_options
     */
    protected function isConsoleCommandContains(array|string $contain_options, array|string $exclude_options = null): bool
    {
        $args = Request::server('argv');
        if (is_array($args)) {
            $command = implode(' ', $args);
            if (
                Str::contains($command, $contain_options) &&
                ($exclude_options == null || ! Str::contains($command, $exclude_options))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add seeds from the $seed_path after the current command in console finished.
     */
    protected function addSeedsAfterConsoleCommandFinished(): void
    {
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            // Accept command in console only,
            // exclude all commands from Artisan::call() method.
            if ($event->output instanceof ConsoleOutput) {
                $this->addSeedsFrom();
            }
        });
    }

    /**
     * Register seeds.
     */
    protected function addSeedsFrom(): void
    {
        // v2
        echo htmlspecialchars("\033[1;33mSeeding:\033[0m $this->namespace\n");
        $startTime = microtime(true);

        Artisan::call('db:seed', ['--class' => $this->namespace, '--force' => '', '-n' => '']);

        $runTime = round(microtime(true) - $startTime, 2);
        echo htmlspecialchars("\033[0;32mSeeded:\033[0m $this->namespace ($runTime seconds)\n");
    }
}
