<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use ErlandMuchasaj\Modules\Traits\MeasuresDirectorySize;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:analyze')]
class ModuleAnalyzeCommand extends Command
{
    use MeasuresDirectorySize;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:analyze
                          {module? : Specific module to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze modules and suggest optimizations';

    public function handle(ModuleCacheManager $cache): int
    {
        $module = $this->argument('module');

        if ($module) {
            $this->analyzeModule($module, $cache);
        } else {
            $modules = $cache->getRegisteredModules();
            foreach ($modules as $mod) {
                $this->analyzeModule($mod, $cache);
                $this->newLine();
            }
        }

        return self::SUCCESS;
    }

    protected function analyzeModule(string $module, ModuleCacheManager $cache): void
    {
        $this->info("Analyzing module: {$module}");
        $this->newLine();

        $issues = [];
        $suggestions = [];

        // Check for config file
        $config = $cache->getModuleConfig($module);
        if (!$config) {
            $issues[] = '⚠ No configuration file found';
            $suggestions[] = 'Consider adding a config/config.php file';
        }

        // Check migrations
        $migrations = $cache->cacheMigrations($module);
        if (empty($migrations)) {
            $issues[] = '⚠ No migrations found';
        } elseif (count($migrations) > 50) {
            $issues[] = '⚠ Large number of migrations (' . count($migrations) . ')';
            $suggestions[] = 'Consider squashing old migrations';
        }

        // Check views
        $views = $cache->cacheViews($module);
        if (count($views) > 100) {
            $issues[] = '⚠ Large number of views (' . count($views) . ')';
            $suggestions[] = 'Consider breaking down complex views into components';
        }

        // Check module size
        $path = base_path($cache->modulesDirectory() . "/{$module}");
        $size = $this->getDirectorySize($path);
        if ($size > 10 * 1024 * 1024) { // > 10MB
            $issues[] = '⚠ Large module size (' . $this->formatBytes($size) . ')';
            $suggestions[] = 'Review assets and consider moving large files to CDN';
        }

        // Display results
        if (empty($issues)) {
            $this->info('✓ No issues found');
        } else {
            $this->warn('Issues found:');
            foreach ($issues as $issue) {
                $this->line("  {$issue}");
            }
        }

        if (!empty($suggestions)) {
            $this->newLine();
            $this->comment('Suggestions:');
            foreach ($suggestions as $suggestion) {
                $this->line("  → {$suggestion}");
            }
        }
    }
}
