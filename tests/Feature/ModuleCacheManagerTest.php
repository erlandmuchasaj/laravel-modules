<?php

use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    Cache::flush();

    $this->modulesPath = base_path('test-modules');
    File::ensureDirectoryExists($this->modulesPath);
    config(['modules.folder' => 'test-modules']);

    // Set up one valid module
    $providerPath = $this->modulesPath . '/Alpha/src/Providers';
    File::ensureDirectoryExists($providerPath);
    File::put($providerPath . '/AppServiceProvider.php', '<?php');

    $this->manager = new ModuleCacheManager();
});

afterEach(function () {
    File::deleteDirectory($this->modulesPath);
    Cache::flush();
});

it('getRegisteredModules caches on second call', function () {
    // First call hits the filesystem
    $first = $this->manager->getRegisteredModules();

    // Remove the file to prove the second call uses cache
    File::deleteDirectory($this->modulesPath . '/Alpha');

    $second = $this->manager->getRegisteredModules();

    expect($second)->toBe($first)->toContain('Alpha');
});

it('clearAll invalidates the registered-modules cache', function () {
    $this->manager->getRegisteredModules(); // prime the cache

    $this->manager->clearAll();

    // Remove the module so fresh discovery returns []
    File::deleteDirectory($this->modulesPath . '/Alpha');

    $fresh = $this->manager->getRegisteredModules();

    expect($fresh)->toBe([]);
});

it('warmUp primes registered, config, migrations and routes caches', function () {
    // Create minimal module structure for warmUp to succeed
    $migPath = $this->modulesPath . '/Alpha/database/migrations';
    $viewPath = $this->modulesPath . '/Alpha/resources/views';
    $cfgPath  = $this->modulesPath . '/Alpha/config';
    File::ensureDirectoryExists($migPath);
    File::ensureDirectoryExists($viewPath);
    File::ensureDirectoryExists($cfgPath);
    File::put($cfgPath . '/config.php', "<?php\nreturn [];");

    $this->manager->warmUp();

    expect($this->manager->hasCached('Alpha', 'migrations'))->toBeTrue()
        ->and($this->manager->hasCached('Alpha', 'views'))->toBeTrue();

    expect(Cache::has('modules.routes.manifest'))->toBeTrue();
});

it('getStats returns the expected shape', function () {
    $stats = $this->manager->getStats();

    expect($stats)->toHaveKeys(['total_modules', 'cached_configs', 'cached_migrations', 'cached_views'])
        ->and($stats['total_modules'])->toBe(1)
        ->and($stats['cached_configs'])->toBe(0)
        ->and($stats['cached_migrations'])->toBe(0)
        ->and($stats['cached_views'])->toBe(0);
});

it('getStats reflects warmed-up caches accurately', function () {
    File::ensureDirectoryExists($this->modulesPath . '/Alpha/database/migrations');
    File::ensureDirectoryExists($this->modulesPath . '/Alpha/resources/views');
    File::ensureDirectoryExists($this->modulesPath . '/Alpha/config');
    File::put($this->modulesPath . '/Alpha/config/config.php', "<?php\nreturn [];");

    $this->manager->warmUp();
    $stats = $this->manager->getStats();

    expect($stats['cached_migrations'])->toBe(1)
        ->and($stats['cached_views'])->toBe(1);
});

it('clearModule removes only that module\'s cache entries', function () {
    // Warm up so Alpha has cached entries
    File::ensureDirectoryExists($this->modulesPath . '/Alpha/database/migrations');
    File::ensureDirectoryExists($this->modulesPath . '/Alpha/resources/views');

    $this->manager->cacheMigrations('Alpha');

    expect($this->manager->hasCached('Alpha', 'migrations'))->toBeTrue();

    $this->manager->clearModule('Alpha');

    expect($this->manager->hasCached('Alpha', 'migrations'))->toBeFalse();
});
