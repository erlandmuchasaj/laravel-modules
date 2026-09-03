<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->modulesPath = base_path('modules');
    $this->moduleName  = 'TestGen';
    $this->modulePath  = $this->modulesPath . '/' . $this->moduleName;
});

afterEach(function () {
    if (File::isDirectory($this->modulePath)) {
        File::deleteDirectory($this->modulePath);
    }
});

it('module:make creates the expected directory tree', function () {
    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true])
        ->assertExitCode(0);

    expect(is_dir($this->modulePath))->toBeTrue()
        ->and(is_dir($this->modulePath . '/src/Providers'))->toBeTrue()
        ->and(is_dir($this->modulePath . '/src/Http/Controllers'))->toBeTrue()
        ->and(is_dir($this->modulePath . '/database/migrations'))->toBeTrue()
        ->and(is_dir($this->modulePath . '/database/factories'))->toBeTrue()
        ->and(is_dir($this->modulePath . '/database/seeders'))->toBeTrue()
        ->and(is_dir($this->modulePath . '/routes'))->toBeTrue()
        ->and(is_dir($this->modulePath . '/resources/views'))->toBeTrue()
        ->and(is_dir($this->modulePath . '/tests/Feature'))->toBeTrue()
        ->and(is_dir($this->modulePath . '/tests/Unit'))->toBeTrue();
});

it('module:make creates AppServiceProvider.php', function () {
    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true])
        ->assertExitCode(0);

    $providerFile = $this->modulePath . '/src/Providers/AppServiceProvider.php';

    expect(file_exists($providerFile))->toBeTrue()
        ->and(file_get_contents($providerFile))->toContain($this->moduleName);
});

it('module:make creates composer.json with the correct namespace', function () {
    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true])
        ->assertExitCode(0);

    $composerFile = $this->modulePath . '/composer.json';

    expect(file_exists($composerFile))->toBeTrue();

    $json = json_decode(file_get_contents($composerFile), true);

    expect($json)->not->toBeNull()
        ->and($json['autoload']['psr-4'])->toHaveKey("Modules\\{$this->moduleName}\\");
});

it('module:make creates the web, api and channels route files', function () {
    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true])
        ->assertExitCode(0);

    expect(file_exists($this->modulePath . '/routes/web.php'))->toBeTrue()
        ->and(file_exists($this->modulePath . '/routes/api.php'))->toBeTrue()
        ->and(file_exists($this->modulePath . '/routes/channels.php'))->toBeTrue();
});

it('--no-register skips composer.json modification', function () {
    $rootComposerBefore = json_decode(File::get(base_path('composer.json')), true);

    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true])
        ->assertExitCode(0);

    $rootComposerAfter = json_decode(File::get(base_path('composer.json')), true);

    $keyName = 'modules/' . strtolower($this->moduleName) . '/' . strtolower($this->moduleName);

    expect(isset($rootComposerAfter['require'][$keyName]))->toBeFalse(
        'Root composer.json should not be modified when --no-register is used'
    );
});

it('module:make fails gracefully when module already exists', function () {
    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true]);

    // Running again should fail
    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true])
        ->assertExitCode(1);
});
