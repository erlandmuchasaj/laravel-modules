<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\SeedOrchestrator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:seed-list')]
class ModuleSeedListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:seed-list 
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered module seeders and their execution order';

    /**
     * Execute the console command.
     *
     * @throws \ReflectionException
     */
    public function handle(SeedOrchestrator $orchestrator): int
    {
        $seeders = $orchestrator->getSeeders();

        if (empty($seeders)) {
            $this->warn('No module seeders registered.');
            return self::SUCCESS;
        }

        // Sort seeders as they would be executed
        $reflection = new \ReflectionClass($orchestrator);
        $method = $reflection->getMethod('sortSeeders');
        $method->setAccessible(true);
        $sorted = $method->invoke($orchestrator, $seeders);

        if ($this->option('json')) {
            $this->line(json_encode($sorted, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info('Module Seeders Execution Order:');
        $this->newLine();

        $tableData = [];
        foreach ($sorted as $index => $seeder) {
            $namespace = $seeder['namespace'];
            $module = $this->extractModuleName($namespace);

            $dependencies = empty($seeder['dependencies'])
                ? '-'
                : implode(', ', array_map(fn($dep) => class_basename($dep), $seeder['dependencies']));

            $tableData[] = [
                $index + 1,
                $module,
                class_basename($seeder['namespace']),
                $seeder['priority'],
                $dependencies,
            ];
        }

        $this->table(
            ['Order', 'Module', 'Seeder', 'Priority', 'Dependencies'],
            $tableData
        );

        $this->newLine();
        $this->info('Total: ' . count($sorted) . ' seeders');

        return self::SUCCESS;
    }

    /**
     * Extract the module name from the full namespace.
     *
     * Example:
     *  Modules\\Info\\Database\\Seeders\\DatabaseSeeder → Info
     */
    protected function extractModuleName(string $namespace): string
    {
        if (preg_match('/^Modules\\\\([^\\\\]+)/', $namespace, $matches)) {
            return $matches[1];
        }

        return 'N/A';
    }
}
