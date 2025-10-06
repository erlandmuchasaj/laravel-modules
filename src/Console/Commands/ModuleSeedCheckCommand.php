<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\SeedOrchestrator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:seed-check')]
class ModuleSeedCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:seed-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for circular dependencies and seed order issues';

    /**
     * Execute the console command.
     *
     * @throws \ReflectionException
     */
    public function handle(SeedOrchestrator $orchestrator): int
    {
        $this->info('Checking module seed configuration...');
        $this->newLine();

        $seeders = $orchestrator->getSeeders();

        if (empty($seeders)) {
            $this->warn('No module seeders registered.');
            return self::SUCCESS;
        }

        // Check for circular dependencies
        try {
            $reflection = new \ReflectionClass($orchestrator);
            $method = $reflection->getMethod('sortSeeders');
            $method->setAccessible(true);
            $sorted = $method->invoke($orchestrator, $seeders);

            $this->info('✓ No circular dependencies detected');
        } catch (\RuntimeException $e) {
            $issues[] = '✗ Circular dependency detected: ' . $e->getMessage();
        }


        // Check for missing dependencies
        $registeredSeeders = array_column($seeders, 'namespace');

        foreach ($seeders as $seeder) {
            foreach ($seeder['dependencies'] as $dependency) {
                if (!in_array($dependency, $registeredSeeders)) {
                    $issues[] = "✗ Missing dependency: {$seeder['namespace']} depends on {$dependency} which is not registered";
                }
            }
        }

        if (empty($issues)) {
            $this->info('✓ No missing dependencies');
        }

        // Check for priority conflicts
        $priorities = array_column($seeders, 'priority');
        if (count($priorities) !== count(array_unique($priorities))) {
            $this->warn('⚠ Multiple seeders have the same priority (this is OK, but order may vary)');
        }

        $this->newLine();

        if (empty($issues)) {
            $this->info('All checks passed! ✓');
            return self::SUCCESS;
        }

        $this->error('Issues found:');
        foreach ($issues as $issue) {
            $this->line($issue);
        }

        return self::FAILURE;
    }

}
