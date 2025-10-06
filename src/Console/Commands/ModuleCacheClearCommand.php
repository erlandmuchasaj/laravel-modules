<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:cache-clear')]
class ModuleCacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:cache-clear
                          {module? : The module name to clear cache for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear module caches';

    public function handle(ModuleCacheManager $cache): int
    {
        $module = $this->argument('module');

        if ($module) {
            $cache->clearModule($module);
            $this->info("Cache cleared for module: {$module}");
        } else {
            $cache->clearAll();
            $this->info('All module caches cleared!');
        }

        return self::SUCCESS;
    }
}
