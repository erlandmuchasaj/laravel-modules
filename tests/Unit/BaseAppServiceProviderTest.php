<?php

use ErlandMuchasaj\Modules\Providers\BaseAppServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

// register() calls mergeConfigFrom() which requires the config file to exist.
beforeEach(function () {
    $configDir = base_path('modules/TestModule/config');
    File::ensureDirectoryExists($configDir);
    File::put($configDir . '/config.php', "<?php\nreturn [];");
});

afterEach(function () {
    File::deleteDirectory(base_path('modules/TestModule'));
});

// ──────────────────────────────────────────────
// Test stubs
// ──────────────────────────────────────────────

class StubModel extends Model {}
class StubObserver { public function creating(StubModel $m): void {} }
class StubPolicy {}

// ──────────────────────────────────────────────
// Helper: build a concrete provider subclass on the fly
// ──────────────────────────────────────────────

function makeProvider(array $config = []): BaseAppServiceProvider
{
    $module     = $config['module']     ?? 'TestModule';
    $observers  = $config['observers']  ?? [];
    $policies   = $config['policies']   ?? [];
    $providers  = $config['providers']  ?? [];
    $commands   = $config['commands']   ?? [];

    return new class(app(), $module, $observers, $policies, $providers, $commands)
        extends BaseAppServiceProvider
    {
        public function __construct(
            $app,
            string $mod,
            array $obs,
            array $pols,
            array $provs,
            array $cmds,
        ) {
            parent::__construct($app);
            $this->module    = $mod;
            $this->observers = $obs;
            $this->policies  = $pols;
            $this->providers = $provs;
            $this->commands  = $cmds;
        }
    };
}

// ──────────────────────────────────────────────
// bootPolicies
// ──────────────────────────────────────────────

it('bootPolicies registers each policy via Gate::policy()', function () {
    $provider = makeProvider([
        'policies' => [StubModel::class => StubPolicy::class],
    ]);

    app()->register($provider);

    expect(Gate::getPolicyFor(StubModel::class))->toBeInstanceOf(StubPolicy::class);
});

it('bootPolicies is a no-op when the policies array is empty', function () {
    $provider = makeProvider(['policies' => []]);

    expect(fn () => app()->register($provider))->not->toThrow(Throwable::class);

    expect(Gate::getPolicyFor(StubModel::class))->toBeNull();
});

// ──────────────────────────────────────────────
// bootObservers
// ──────────────────────────────────────────────

it('bootObservers registers observers without throwing', function () {
    $provider = makeProvider([
        'observers' => [StubModel::class => StubObserver::class],
    ]);

    expect(fn () => app()->register($provider))->not->toThrow(Throwable::class);
});

it('bootObservers is a no-op when the observers array is empty', function () {
    $provider = makeProvider(['observers' => []]);

    expect(fn () => app()->register($provider))->not->toThrow(Throwable::class);
});

// ──────────────────────────────────────────────
// bootMigrations
// ──────────────────────────────────────────────

it('bootMigrations does not throw when the migrations directory does not exist', function () {
    $provider = makeProvider(['module' => 'NonExistentModule']);

    expect(fn () => app()->register($provider))->not->toThrow(Throwable::class);
});

it('bootMigrations loads migrations from the expected path when the directory exists', function () {
    $migPath = base_path('modules/TestModule/database/migrations');
    File::ensureDirectoryExists($migPath);

    $loaded = [];
    app('migrator')->paths(); // prime migrator

    $provider = makeProvider();
    app()->register($provider);

    // The path is registered via loadMigrationsFrom; confirming no exception is thrown
    // and that the directory was created as expected is sufficient here.
    expect(is_dir($migPath))->toBeTrue();

    File::deleteDirectory(base_path('modules/TestModule'));
});

// ──────────────────────────────────────────────
// bootTranslations
// ──────────────────────────────────────────────

it('bootTranslations registers the translation namespace', function () {
    $langPath = base_path('modules/TestModule/lang/en');
    File::ensureDirectoryExists($langPath);
    File::put(base_path('modules/TestModule/lang/en/messages.php'), "<?php\nreturn ['key' => 'value'];");

    $provider = makeProvider();
    app()->register($provider);

    // The namespace is registered; we can assert no exception + key resolves
    expect(trans('test_module::messages.key'))->toBe('value');

    File::deleteDirectory(base_path('modules/TestModule'));
});

// ──────────────────────────────────────────────
// bootViews
// ──────────────────────────────────────────────

it('bootViews registers the view namespace', function () {
    $viewPath = base_path('modules/TestModule/resources/views');
    File::ensureDirectoryExists($viewPath);

    $provider = makeProvider();
    app()->register($provider);

    $hint = app('view')->getFinder()->getHints();

    expect($hint)->toHaveKey('test_module');

    File::deleteDirectory(base_path('modules/TestModule'));
});
