<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use ErlandMuchasaj\Modules\Traits\MeasuresDirectorySize;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:list')]
class ModuleListCommand extends Command
{
    use MeasuresDirectorySize;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:list
                          {--detailed : Show detailed information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show a list of all modules.';

    /**
     * Execute the console command.
     */
    public function handle(ModuleCacheManager $cache): int
    {
        $modules = $cache->getRegisteredModules();

        if (empty($modules)) {
            $this->warn('No modules found.');
            return self::SUCCESS;
        }

        $this->info('Registered Modules:');
        $this->newLine();

        if ($this->option('detailed')) {
            $this->showDetailedList($modules, $cache);
        } else {
            $this->showSimpleList($modules);
        }

        $this->newLine();
        $this->info('Total: ' . count($modules) . ' modules');

        return self::SUCCESS;
    }

    protected function showSimpleList(array $modules): void
    {
        foreach ($modules as $index => $module) {
            $this->line(($index + 1) . ". {$module}");
        }
    }

    protected function showDetailedList(array $modules, ModuleCacheManager $cache): void
    {
        $tableData = [];

        foreach ($modules as $module) {
            $migrations = count($cache->cacheMigrations($module));
            $views = count($cache->cacheViews($module));
            $hasConfig = $cache->getModuleConfig($module) !== null;

            $tableData[] = [
                $module,
                $hasConfig ? 'Yes' : 'No',
                $migrations,
                $views,
                $this->getModuleSize($cache, $module),
            ];
        }

        $this->table(
            ['Module', 'Has Config', 'Migrations', 'Views', 'Size'],
            $tableData
        );
    }

    protected function getModuleSize(ModuleCacheManager $cache, string $module): string
    {
        $path = base_path($cache->modulesDirectory() . "/{$module}");

        if (!is_dir($path)) {
            return 'N/A';
        }

        return $this->formatBytes($this->getDirectorySize($path));
    }
}
