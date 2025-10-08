<?php

namespace ErlandMuchasaj\Modules\Support;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Orchestrates the registration and execution of seeders with priority and dependency management.
 */
class SeedOrchestrator
{
    /**
     * @var array<string, array{namespace: string, priority: int, dependencies: array}>
     */
    protected array $seeders = [];

    /**
     * @var bool
     */
    protected bool $hasRun = false;

    /**
     * Register a seeder with priority and dependencies.
     *
     * @param string $namespace Seeder class namespace
     * @param int $priority Higher numbers run first (default: 100)
     * @param array $dependencies Seeder class names that must run before this one
     */
    public function register(string $namespace, int $priority = 100, array $dependencies = []): void
    {
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
        $sorted = $this->sortSeeders($this->seeders);
        foreach ($sorted as $seeder) {
            $this->executeSeed($seeder['namespace']);
        }
    }

    /**
     * Sort seeders by priority and dependencies.
     *
     * @param array $seeders
     * @return array
     */
    public function sortSeeders(array $seeders): array
    {
        // Sort by priority (descending)
        usort($seeders, static fn($a, $b) => $b['priority'] <=> $a['priority']);
        // Resolve dependencies using topological sort
        return $this->topologicalSort($seeders);
    }

    /**
     * Topological sort to handle dependencies.
     *
     * @param array $seeders
     * @return array
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
                    if (!isset($seederMap[$dependency])) {
                        throw new \RuntimeException("Missing dependency seeder: {$dependency} required by {$namespace}");
                    }
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
     *
     * @param string $namespace
     */
    protected function executeSeed(string $namespace): void
    {
        $output = new ConsoleOutput();
        $output->writeln("<comment>Seeding:</comment> $namespace");
        $startTime = microtime(true);
        Artisan::call('db:seed', [
            '--class' => $namespace,
            '--force' => true,
        ]);
        $runTime = round(microtime(true) - $startTime, 2);
        $output->writeln("<info>Seeded:</info> {$namespace} ({$runTime} seconds)");
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
     *
     * @return array<string, array>
     */
    public function getSeeders(): array
    {
        return $this->seeders;
    }

}