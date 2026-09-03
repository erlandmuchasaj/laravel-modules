<?php

use ErlandMuchasaj\Modules\Traits\CanPublishConfiguration;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

// Concrete service-provider that uses the trait
function makeConfigProvider(): ServiceProvider
{
    return new class(app()) extends ServiceProvider {
        use CanPublishConfiguration;

        public function register(): void {}
        public function boot(): void {}

        public function callRegisterConfig(string $module, string $fileName): void
        {
            $this->registerConfig($module, $fileName);
        }

        public function callPublishConfig(string $module, string $fileName): void
        {
            $this->publishConfig($module, $fileName);
        }
    };
}

beforeEach(function () {
    $this->moduleName = 'ConfigModule';
    $this->modulePath = base_path('modules/' . $this->moduleName);
    $this->configPath = $this->modulePath . '/config';
    File::ensureDirectoryExists($this->configPath);
    File::put($this->configPath . '/settings.php', "<?php\nreturn ['key' => 'value'];");
});

afterEach(function () {
    File::deleteDirectory($this->modulePath);
});

// ──────────────────────────────────────────────
// registerConfig — merges config into application
// ──────────────────────────────────────────────

it('registerConfig merges the module config into the application config', function () {
    $provider = makeConfigProvider();
    $provider->callRegisterConfig($this->moduleName, 'settings');

    expect(config('modules.' . strtolower($this->moduleName) . '.settings'))->toBe(['key' => 'value']);
});

// ──────────────────────────────────────────────
// publishConfig in testing environment
// ──────────────────────────────────────────────

it('publishConfig in testing env merges config without throwing', function () {
    $provider = makeConfigProvider();

    // bootConfig is skipped in the testing environment; registerConfig still runs
    expect(fn () => $provider->callPublishConfig($this->moduleName, 'settings'))->not->toThrow(Throwable::class);

    expect(config('modules.' . strtolower($this->moduleName) . '.settings'))->toBe(['key' => 'value']);
});

// ──────────────────────────────────────────────
// Config key format
// ──────────────────────────────────────────────

it('accessing a config key for a missing file does not throw', function () {
    $provider = makeConfigProvider();

    // The config file for 'my-config' does not exist — mergeConfigFrom handles missing files gracefully
    expect(fn () => $provider->callRegisterConfig('MyModule', 'my-config'))->not->toThrow(Throwable::class);
});
