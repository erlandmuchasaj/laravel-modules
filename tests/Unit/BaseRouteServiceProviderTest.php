<?php

use ErlandMuchasaj\Modules\Providers\BaseRouteServiceProvider;

// Build a concrete subclass on the fly
function makeRouteProvider(string $web = '', string $api = '', string $channels = ''): BaseRouteServiceProvider
{
    return new class(app(), $web, $api, $channels) extends BaseRouteServiceProvider {
        public function __construct(
            $app,
            private string $webPath,
            private string $apiPath,
            private string $chanPath,
        ) {
            parent::__construct($app);
        }

        protected function getWebRoute(): string      { return $this->webPath; }
        protected function getApiRoute(): string      { return $this->apiPath; }
        protected function getChannelsRoute(): string { return $this->chanPath; }

        public function routeFileExists(string $path): bool { return parent::routeFileExists($path); }
        public function routesAreCached(): bool             { return parent::routesAreCached(); }
    };
}

// ──────────────────────────────────────────────
// routeFileExists
// ──────────────────────────────────────────────

it('routeFileExists returns false for a non-existent path', function () {
    $provider = makeRouteProvider();

    expect($provider->routeFileExists('/nonexistent/path/routes.php'))->toBeFalse();
});

it('routeFileExists returns true for an existing file', function () {
    $tmp = sys_get_temp_dir() . '/test_route_' . uniqid() . '.php';
    file_put_contents($tmp, '<?php');

    $provider = makeRouteProvider();

    expect($provider->routeFileExists($tmp))->toBeTrue();

    unlink($tmp);
});

it('routeFileExists returns false for an empty string path', function () {
    $provider = makeRouteProvider();

    expect($provider->routeFileExists(''))->toBeFalse();
});

it('routeFileExists result is cached per-instance on subsequent calls', function () {
    $tmp = sys_get_temp_dir() . '/test_route_cache_' . uniqid() . '.php';
    file_put_contents($tmp, '<?php');

    $provider = makeRouteProvider();

    $first = $provider->routeFileExists($tmp);
    unlink($tmp); // delete the file — cache should serve the previous result

    $second = $provider->routeFileExists($tmp);

    expect($first)->toBeTrue()
        ->and($second)->toBeTrue(); // still true from cache
});

// ──────────────────────────────────────────────
// routesAreCached
// ──────────────────────────────────────────────

it('routesAreCached returns false in the test environment', function () {
    $provider = makeRouteProvider();

    expect($provider->routesAreCached())->toBeFalse();
});

// ──────────────────────────────────────────────
// map — skips when route files do not exist
// ──────────────────────────────────────────────

it('map does not throw when route files do not exist', function () {
    $provider = makeRouteProvider('', '', '');
    app()->register($provider);

    expect(fn () => $provider->map(app('router')))->not->toThrow(Throwable::class);
});

it('map registers web routes when the web route file exists', function () {
    $webFile = sys_get_temp_dir() . '/web_' . uniqid() . '.php';
    file_put_contents($webFile, "<?php\nuse Illuminate\\Support\\Facades\\Route;\nRoute::get('/ext-test-route', fn() => 'ok');");

    $provider = makeRouteProvider(web: $webFile);
    app()->register($provider);
    $provider->map(app('router'));

    $uris = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($r) => $r->uri())
        ->all();

    expect(in_array('ext-test-route', $uris))->toBeTrue();

    unlink($webFile);
});
