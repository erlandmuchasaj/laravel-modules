<?php

use ErlandMuchasaj\Modules\Console\Commands\ModuleMakeCommand;
use Illuminate\Support\Facades\File;

const HARDENING_MODULE = 'HardeningMod';

beforeEach(function () {
    $this->moduleName = HARDENING_MODULE;
    $this->modulePath = base_path('modules/'.$this->moduleName);
});

afterEach(function () {
    foreach ([$this->modulePath, base_path('modules/1Shop'), base_path('modules/Cache'), base_path('custom')] as $path) {
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }
    }
});

// ──────────────────────────────────────────────
// Module name validation
// ──────────────────────────────────────────────

it('module:make rejects a name starting with a number', function () {
    $this->artisan('module:make', ['module' => '1Shop', '--no-register' => true])
        ->assertExitCode(1);

    expect(is_dir(base_path('modules/1Shop')))->toBeFalse();
});

it('module:make still creates a module named after a package config key', function () {
    $this->artisan('module:make', ['module' => 'Cache', '--no-register' => true])
        ->assertExitCode(0);

    expect(is_dir(base_path('modules/Cache')))->toBeTrue();
});

it('module:make-migration rejects a module name containing path characters', function () {
    expect(fn () => $this->artisan('module:make-migration', ['module' => '../Evil', 'name' => 'create_evil_table'])->run())
        ->toThrow(InvalidArgumentException::class);
});

it('module:extract-translation rejects a module name containing path characters', function () {
    expect(fn () => $this->artisan('module:extract-translation', ['module' => '../Evil'])->run())
        ->toThrow(InvalidArgumentException::class);
});

// ──────────────────────────────────────────────
// module:make-test --pest
// ──────────────────────────────────────────────

it('module:make-test --pest creates a Pest feature test', function () {
    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true]);

    $this->artisan('module:make-test', ['module' => $this->moduleName, 'name' => 'ArticlePestTest', '--pest' => true])
        ->assertExitCode(0);

    expect(file_exists($this->modulePath.'/tests/Feature/ArticlePestTest.php'))->toBeTrue();
});

// ──────────────────────────────────────────────
// Custom modules folder / namespace
// ──────────────────────────────────────────────

it('generators honour a custom modules folder', function () {
    config()->set('modules.folder', 'custom');

    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true])
        ->assertExitCode(0);

    $this->artisan('module:make-test', ['module' => $this->moduleName, 'name' => 'FolderTest'])
        ->assertExitCode(0);

    $this->artisan('module:make-seeder', ['module' => $this->moduleName, 'name' => 'FolderSeeder'])
        ->assertExitCode(0);

    expect(file_exists(base_path('custom/'.$this->moduleName.'/tests/Feature/FolderTest.php')))->toBeTrue()
        ->and(file_exists(base_path('custom/'.$this->moduleName.'/database/seeders/FolderSeeder.php')))->toBeTrue()
        ->and(is_dir($this->modulePath))->toBeFalse();
});

it('the module composer package name is unchanged for the default folder', function () {
    $command = app(ModuleMakeCommand::class);
    $command->setLaravel(app());
    $method = new ReflectionMethod($command, 'getModulePackageName');

    expect($method->invoke($command, 'BlogPost'))->toBe('modules/blog-post');

    config()->set('modules.folder', 'app/Modules');

    expect($method->invoke($command, 'BlogPost'))->toBe('modules/blog-post');
});
