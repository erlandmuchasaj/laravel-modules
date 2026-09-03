<?php

use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    Cache::flush();

    // Use the testbench app's base path for a temporary modules directory
    $this->modulesPath = base_path('test-modules');
    File::ensureDirectoryExists($this->modulesPath);

    config(['modules.folder' => 'test-modules']);

    $this->manager = new ModuleCacheManager();
});

afterEach(function () {
    File::deleteDirectory($this->modulesPath);
    Cache::flush();
});

it('discovers modules that have a valid AppServiceProvider', function () {
    // Create a valid module structure
    $providerPath = $this->modulesPath . '/ValidModule/src/Providers';
    File::ensureDirectoryExists($providerPath);
    File::put($providerPath . '/AppServiceProvider.php', '<?php // stub');

    $modules = $this->manager->getRegisteredModules();

    expect($modules)->toContain('ValidModule');
});

it('ignores directories without an AppServiceProvider', function () {
    // Empty directory — no AppServiceProvider
    File::ensureDirectoryExists($this->modulesPath . '/EmptyDir');

    $modules = $this->manager->getRegisteredModules();

    expect($modules)->not->toContain('EmptyDir');
});

it('returns only module names, not full paths', function () {
    File::ensureDirectoryExists($this->modulesPath . '/Alpha/src/Providers');
    File::put($this->modulesPath . '/Alpha/src/Providers/AppServiceProvider.php', '<?php');

    File::ensureDirectoryExists($this->modulesPath . '/Beta/src/Providers');
    File::put($this->modulesPath . '/Beta/src/Providers/AppServiceProvider.php', '<?php');

    $modules = $this->manager->getRegisteredModules();

    expect($modules)
        ->toContain('Alpha')
        ->toContain('Beta')
        ->each->not->toContain('/'); // names only, no slashes
});

it('returns an empty array when modules directory does not exist', function () {
    config(['modules.folder' => 'nonexistent-modules']);

    $manager = new ModuleCacheManager();
    $modules = $manager->getRegisteredModules();

    expect($modules)->toBe([]);
});

it('does not discover a directory that has providers in the wrong location', function () {
    // AppServiceProvider at wrong path — not src/Providers
    File::ensureDirectoryExists($this->modulesPath . '/BadModule/Providers');
    File::put($this->modulesPath . '/BadModule/Providers/AppServiceProvider.php', '<?php');

    $modules = $this->manager->getRegisteredModules();

    expect($modules)->not->toContain('BadModule');
});
