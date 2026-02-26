<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:list')]
class ModuleListCommand extends Command
{
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


        // $this->components->twoColumnDetail('<fg=gray>Index/Name</>');
        // collect($this->getModules())->each(function ($item, $key) {
        //     $this->components->twoColumnDetail("[{$key}] / ".basename($item));
        // });

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
                $this->getModuleSize($module),
            ];
        }

        $this->table(
            ['Module', 'Has Config', 'Migrations', 'Views', 'Size'],
            $tableData
        );
    }

    protected function getModuleSize(string $module): string
    {
        $path = base_path($this->modulesDirectory() . "/{$module}");

        if (!is_dir($path)) {
            return 'N/A';
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $this->formatBytes($size);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Return a list of all modules
     *
     * @return array<int, string>
     */
    public function getModules(): array
    {
        $modulesPath = base_path($this->modulesDirectory());

        if (! File::isDirectory($modulesPath)) {
            return [];
        }

        return File::directories($modulesPath);
    }

    protected function modulesDirectory(): string
    {
        $folder = config('modules.folder') ?: config('modules.base', 'modules');

        return trim((string) $folder, '/\\') ?: 'modules';
    }
}
