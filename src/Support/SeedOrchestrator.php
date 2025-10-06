<?php

namespace ErlandMuchasaj\Modules\Support;

use Illuminate\Support\Facades\Artisan;

class SeedOrchestrator
{
    protected array $seeders = [];

    protected bool $hasRun = false;

    /**
     * Register a seeder with priority.
     *
     * @param string $namespace The seeder class namespace
     * @param int $priority Higher numbers run first (default: 100)
     * @param array $dependencies Array of seeder class names that must run before this one
     */
    public function register(string $namespace, int $priority = 100, array $dependencies = []): void
    {
        // Skip if already registered
        if (isset($this->seeders[$namespace])) {
            return;
        }

        $this->seeders[$namespace] = [
            'namespace' => $namespace,
            'priority' => $priority,
            'dependencies' => $dependencies,
        ];
    }


    /**
     * Execute all registered seeders in order.
     */
    public function run(): void
    {
        if ($this->hasRun) {
            return;
        }

        $this->hasRun = true;

        // Sort by priority (higher first), then resolve dependencies
        $sorted = $this->sortSeeders($this->seeders);

        foreach ($sorted as $seeder) {
            $this->executeSeed($seeder['namespace']);
        }
    }

    /**
     * Sort seeders by priority and dependencies.
     */
    public function sortSeeders(array $seeders): array
    {
        // First, sort by priority (descending)
        usort($seeders, fn($a, $b) => $b['priority'] <=> $a['priority']);

        // Then, resolve dependencies using topological sort
        return $this->topologicalSort($seeders);
    }

    /**
     * Topological sort to handle dependencies.
     */
    protected function topologicalSort(array $seeders): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        $seederMap = [];
        foreach ($seeders as $seeder) {
            $seederMap[$seeder['namespace']] = $seeder;
        }

        $visit = function ($namespace) use (&$visit, &$sorted, &$visited, &$visiting, $seederMap) {
            if (isset($visited[$namespace])) {
                return;
            }

            if (isset($visiting[$namespace])) {
                throw new \RuntimeException("Circular dependency detected for seeder: {$namespace}");
            }

            $visiting[$namespace] = true;

            if (isset($seederMap[$namespace])) {
                foreach ($seederMap[$namespace]['dependencies'] as $dependency) {
                    $visit($dependency);
                }

                $sorted[] = $seederMap[$namespace];
            }

            $visited[$namespace] = true;
            unset($visiting[$namespace]);
        };

        foreach ($seeders as $seeder) {
            $visit($seeder['namespace']);
        }

        return $sorted;
    }

    /**
     * Execute a single seeder.
     */
    protected function executeSeed(string $namespace): void
    {
        echo htmlspecialchars("\033[1;33mSeeding:\033[0m $namespace\n");
        $startTime = microtime(true);

        Artisan::call('db:seed', [
            '--class' => $namespace,
            '--force' => true,
        ]);

        $runTime = round(microtime(true) - $startTime, 2);
        echo htmlspecialchars("\033[0;32mSeeded:\033[0m {$namespace} ({$runTime} seconds)\n");
    }

    /**
     * Reset the orchestrator (useful for testing).
     */
    public function reset(): void
    {
        $this->seeders = [];
        $this->hasRun = false;
    }

    /**
     * Get all registered seeders.
     */
    public function getSeeders(): array
    {
        return $this->seeders;
    }

}