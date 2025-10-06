<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:cache-stats')]
class ModuleCacheStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:cache-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display module cache statistics';

    public function handle(ModuleCacheManager $cache): int
    {
        $this->info('Module Cache Statistics');
        $this->newLine();

        $stats = $cache->getStats();
        $modules = $cache->getRegisteredModules();

        // Overall stats
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Modules', $stats['total_modules']],
                ['Cached Configs', $stats['cached_configs']],
                ['Cached Migrations', $stats['cached_migrations']],
                ['Cached Views', $stats['cached_views']],
            ]
        );

        $this->newLine();

        // Per-module breakdown
        $this->info('Per-Module Cache Status:');
        $this->newLine();

        $tableData = [];
        foreach ($modules as $module) {
            $tableData[] = [
                $module,
                $this->getCacheStatus($cache, $module, 'config'),
                $this->getCacheStatus($cache, $module, 'migrations'),
                $this->getCacheStatus($cache, $module, 'views'),
            ];
        }

        $this->table(
            ['Module', 'Config', 'Migrations', 'Views'],
            $tableData
        );

        return self::SUCCESS;
    }

    protected function getCacheStatus(ModuleCacheManager $cache, string $module, string $type): string
    {
        $key = "modules.{$type}.{$module}";
        return \Cache::has($key) ? '✓ Cached' : '✗ Not Cached';
    }
}
