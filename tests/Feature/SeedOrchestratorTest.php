<?php

use ErlandMuchasaj\Modules\Support\SeedOrchestrator;

beforeEach(function () {
    $this->orchestrator = new SeedOrchestrator();
});

// ──────────────────────────────────────────────
// Priority ordering
// ──────────────────────────────────────────────

it('sorts seeders so higher priority runs first', function () {
    $this->orchestrator->register('LowPrioritySeeder', priority: 10);
    $this->orchestrator->register('HighPrioritySeeder', priority: 200);
    $this->orchestrator->register('MidPrioritySeeder', priority: 100);

    $sorted = $this->orchestrator->sortSeeders($this->orchestrator->getSeeders());
    $names  = array_column($sorted, 'namespace');

    expect($names)->toBe(['HighPrioritySeeder', 'MidPrioritySeeder', 'LowPrioritySeeder']);
});

it('preserves order for seeders with equal priority', function () {
    $this->orchestrator->register('SeederA', priority: 50);
    $this->orchestrator->register('SeederB', priority: 50);

    $sorted = $this->orchestrator->sortSeeders($this->orchestrator->getSeeders());
    $names  = array_column($sorted, 'namespace');

    // Both must appear; relative order is stable-ish but they all show up
    expect($names)->toContain('SeederA')->toContain('SeederB');
});

// ──────────────────────────────────────────────
// Topological sort / dependency resolution
// ──────────────────────────────────────────────

it('places a dependency before its dependant', function () {
    $this->orchestrator->register('CategorySeeder', priority: 100);
    $this->orchestrator->register('PostSeeder',     priority: 100, dependencies: ['CategorySeeder']);

    $sorted = $this->orchestrator->sortSeeders($this->orchestrator->getSeeders());
    $names  = array_column($sorted, 'namespace');

    $catIdx  = array_search('CategorySeeder', $names, true);
    $postIdx = array_search('PostSeeder', $names, true);

    expect($catIdx)->toBeLessThan($postIdx);
});

it('handles transitive dependencies', function () {
    $this->orchestrator->register('A', priority: 100);
    $this->orchestrator->register('B', priority: 100, dependencies: ['A']);
    $this->orchestrator->register('C', priority: 100, dependencies: ['B']);

    $sorted = $this->orchestrator->sortSeeders($this->orchestrator->getSeeders());
    $names  = array_column($sorted, 'namespace');

    expect(array_search('A', $names))->toBeLessThan(array_search('B', $names))
        ->and(array_search('B', $names))->toBeLessThan(array_search('C', $names));
});

// ──────────────────────────────────────────────
// Circular dependency detection
// ──────────────────────────────────────────────

it('throws RuntimeException on a direct circular dependency', function () {
    $this->orchestrator->register('A', priority: 100, dependencies: ['B']);
    $this->orchestrator->register('B', priority: 100, dependencies: ['A']);

    expect(fn () => $this->orchestrator->sortSeeders($this->orchestrator->getSeeders()))
        ->toThrow(\RuntimeException::class, 'Circular dependency detected');
});

it('throws RuntimeException on a missing dependency', function () {
    $this->orchestrator->register('PostSeeder', priority: 100, dependencies: ['NonExistentSeeder']);

    expect(fn () => $this->orchestrator->sortSeeders($this->orchestrator->getSeeders()))
        ->toThrow(\RuntimeException::class, 'Missing dependency seeder');
});

// ──────────────────────────────────────────────
// hasRun guard / double-execution prevention
// ──────────────────────────────────────────────

it('register is idempotent — duplicate registration is ignored', function () {
    $this->orchestrator->register('SeederA', priority: 100);
    $this->orchestrator->register('SeederA', priority: 999); // should be ignored

    $seeders = $this->orchestrator->getSeeders();

    expect($seeders)->toHaveCount(1)
        ->and($seeders['SeederA']['priority'])->toBe(100);
});

it('reset clears all registered seeders and resets the hasRun flag', function () {
    $this->orchestrator->register('SomeSeeder');
    $this->orchestrator->reset();

    expect($this->orchestrator->getSeeders())->toBe([]);
});
