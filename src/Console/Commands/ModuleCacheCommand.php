<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:cache')]
class ModuleCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache module configurations, routes, and views';

    public function handle(ModuleCacheManager $cache): int
    {
        $this->info('Caching modules...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        // Warm up cache
        $cache->warmUp();

        $progressBar->finish();
        $this->newLine(2);

        // Display statistics
        $stats = $cache->getStats();

        $this->info('✓ Modules cached successfully!');
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Modules', $stats['total_modules']],
                ['Cached Configs', $stats['cached_configs']],
                ['Cached Migrations', $stats['cached_migrations']],
                ['Cached Views', $stats['cached_views']],
            ]
        );

        $this->newLine();
        $this->comment('Tip: Run this command after each deploy to warm the module cache.');

        return self::SUCCESS;
    }
}
