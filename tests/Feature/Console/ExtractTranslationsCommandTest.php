<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->modulesPath = base_path('modules');
    $this->moduleName  = 'TranslationTest';
    $this->modulePath  = $this->modulesPath . '/' . $this->moduleName;

    // Scaffold a minimal module
    $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true]);
});

afterEach(function () {
    if (File::isDirectory($this->modulePath)) {
        File::deleteDirectory($this->modulePath);
    }
});

it('module:extract-translation --create creates the lang directory', function () {
    $langDir = $this->modulePath . '/lang';

    // Remove lang dir if the scaffold already created one
    File::deleteDirectory($langDir);

    $this->artisan('module:extract-translation', [
        'module'   => $this->moduleName,
        '--create' => true,
    ])->assertExitCode(0);

    expect(is_dir($langDir))->toBeTrue();
});

it('module:extract-translation exits 0 when the lang directory already exists', function () {
    $langPath = $this->modulePath . '/lang';
    File::ensureDirectoryExists($langPath);
    File::put($langPath . '/en.json', json_encode(['key' => 'value']));

    // Add a PHP file with a translatable string so the command has something to scan
    $srcPath = $this->modulePath . '/src';
    File::ensureDirectoryExists($srcPath);
    File::put($srcPath . '/Sample.php', "<?php\necho __('hello.world');");

    $this->artisan('module:extract-translation', [
        'module' => $this->moduleName,
    ])->assertExitCode(0);
});
