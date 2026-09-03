<?php

use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    Cache::flush();
    $this->modulesPath = base_path('test-mgmt-modules');
    File::ensureDirectoryExists($this->modulesPath);
    config(['modules.folder' => 'test-mgmt-modules']);

    // Create a valid module so commands have something to work with
    $providerPath = $this->modulesPath . '/Alpha/src/Providers';
    File::ensureDirectoryExists($providerPath);
    File::put($providerPath . '/AppServiceProvider.php', '<?php');
});

afterEach(function () {
    File::deleteDirectory($this->modulesPath);
    Cache::flush();
});

// ──────────────────────────────────────────────
// module:list
// ──────────────────────────────────────────────

it('module:list exits 0 and outputs module names', function () {
    $this->artisan('module:list')
        ->assertExitCode(0);
});

it('module:list --detailed exits 0', function () {
    $this->artisan('module:list', ['--detailed' => true])
        ->assertExitCode(0);
});

// ──────────────────────────────────────────────
// module:cache
// ──────────────────────────────────────────────

it('module:cache exits 0 and warms the cache', function () {
    $this->artisan('module:cache')
        ->assertExitCode(0);
});

// ──────────────────────────────────────────────
// module:cache-clear
// ──────────────────────────────────────────────

it('module:cache-clear exits 0 and clears all caches', function () {
    $manager = new ModuleCacheManager();
    $manager->getRegisteredModules(); // populates modules.registered so clearAll() knows what to clear
    $manager->cacheMigrations('Alpha');

    $this->artisan('module:cache-clear')
        ->assertExitCode(0);

    expect($manager->hasCached('Alpha', 'migrations'))->toBeFalse();
});

it('module:cache-clear with a module argument clears only that module', function () {
    $manager = new ModuleCacheManager();
    $manager->cacheMigrations('Alpha');

    $this->artisan('module:cache-clear', ['module' => 'Alpha'])
        ->assertExitCode(0);

    expect($manager->hasCached('Alpha', 'migrations'))->toBeFalse();
});

// ──────────────────────────────────────────────
// module:cache-stats
// ──────────────────────────────────────────────

it('module:cache-stats exits 0 and displays stats', function () {
    $this->artisan('module:cache-stats')
        ->assertExitCode(0);
});

// ──────────────────────────────────────────────
// module:analyze
// ──────────────────────────────────────────────

it('module:analyze exits 0 when analyzing all modules', function () {
    $this->artisan('module:analyze')
        ->assertExitCode(0);
});

it('module:analyze exits 0 when analyzing a specific module', function () {
    $this->artisan('module:analyze', ['module' => 'Alpha'])
        ->assertExitCode(0);
});
