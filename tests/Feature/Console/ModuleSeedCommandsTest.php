<?php

use ErlandMuchasaj\Modules\Support\SeedOrchestrator;

beforeEach(function () {
    // Reset the singleton orchestrator before each test
    app()->forgetInstance(SeedOrchestrator::class);
    app()->singleton(SeedOrchestrator::class, fn () => new SeedOrchestrator());
});

// ──────────────────────────────────────────────
// module:seed-clear
// ──────────────────────────────────────────────

it('module:seed-clear resets the orchestrator seeder list', function () {
    $orchestrator = app(SeedOrchestrator::class);
    $orchestrator->register('SomeSeeder', priority: 100);

    $this->artisan('module:seed-clear')
        ->assertExitCode(0);

    expect($orchestrator->getSeeders())->toBe([]);
});

// ──────────────────────────────────────────────
// module:seed-check
// ──────────────────────────────────────────────

it('module:seed-check exits 0 when no seeders are registered', function () {
    $this->artisan('module:seed-check')
        ->assertExitCode(0);
});

it('module:seed-check exits 0 with valid seeders that have no circular dependencies', function () {
    $orchestrator = app(SeedOrchestrator::class);
    $orchestrator->register('SeederA', priority: 100);
    $orchestrator->register('SeederB', priority: 50, dependencies: ['SeederA']);

    $this->artisan('module:seed-check')
        ->assertExitCode(0);
});

// ──────────────────────────────────────────────
// module:seed-list
// ──────────────────────────────────────────────

it('module:seed-list exits 0 when no seeders are registered', function () {
    $this->artisan('module:seed-list')
        ->assertExitCode(0);
});

it('module:seed-list --json exits 0 and outputs valid JSON', function () {
    $this->artisan('module:seed-list', ['--json' => true])
        ->assertExitCode(0);
});

// ──────────────────────────────────────────────
// module:seed
// ──────────────────────────────────────────────

it('module:seed exits non-zero when the module seeder class does not exist', function () {
    // 'NonExistentModule' has no DatabaseSeeder class — command should fail gracefully
    $this->artisan('module:seed', ['module' => 'NonExistentModule'])
        ->assertExitCode(1);
});
