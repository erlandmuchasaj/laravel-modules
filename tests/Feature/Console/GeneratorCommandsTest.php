<?php

use Illuminate\Support\Facades\File;

const SCAFFOLD_MODULE = 'ScaffoldTest';

/**
 * All generator tests share a single module scaffold created once before
 * any test in this file runs, and torn down after all tests complete.
 */
beforeAll(function () {
    // beforeAll runs outside the test container — we cannot use the Laravel
    // app here. Module creation happens in the first test instead.
});

beforeEach(function () {
    $this->modulesPath = base_path('modules');
    $this->moduleName  = SCAFFOLD_MODULE;
    $this->modulePath  = $this->modulesPath . '/' . $this->moduleName;

    // Ensure the scaffold module exists (idempotent)
    if (! is_dir($this->modulePath)) {
        $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true]);
    }
});

afterEach(function () {
    if (File::isDirectory($this->modulePath)) {
        File::deleteDirectory($this->modulePath);
    }
});

// ──────────────────────────────────────────────
// Helper: assert a generated file's path and namespace
// ──────────────────────────────────────────────

function assertGenerated(string $path, string $namespace): void
{
    expect(file_exists($path))->toBeTrue("Expected file not found: $path")
        ->and(file_get_contents($path))->toContain($namespace);
}

// ──────────────────────────────────────────────
// module:make-model
// ──────────────────────────────────────────────

it('module:make-model produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-model', ['module' => $this->moduleName, 'name' => 'Article'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Models/Article/Article.php',
        "namespace Modules\\{$this->moduleName}\\Models\\Article"
    );
});

// ──────────────────────────────────────────────
// module:make-controller
// ──────────────────────────────────────────────

it('module:make-controller produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-controller', ['module' => $this->moduleName, 'name' => 'ArticleController'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Http/Controllers/ArticleController.php',
        "namespace Modules\\{$this->moduleName}\\Http\\Controllers"
    );
});

// ──────────────────────────────────────────────
// module:make-event
// ──────────────────────────────────────────────

it('module:make-event produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-event', ['module' => $this->moduleName, 'name' => 'ArticlePublished'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Events/ArticlePublished.php',
        "namespace Modules\\{$this->moduleName}\\Events"
    );
});

// ──────────────────────────────────────────────
// module:make-job
// ──────────────────────────────────────────────

it('module:make-job produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-job', ['module' => $this->moduleName, 'name' => 'ProcessArticleJob'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Jobs/ProcessArticleJob.php',
        "namespace Modules\\{$this->moduleName}\\Jobs"
    );
});

// ──────────────────────────────────────────────
// module:make-listener
// ──────────────────────────────────────────────

it('module:make-listener produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-listener', ['module' => $this->moduleName, 'name' => 'SendArticleNotification'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Listeners/SendArticleNotification.php',
        "namespace Modules\\{$this->moduleName}\\Listeners"
    );
});

// ──────────────────────────────────────────────
// module:make-notification
// ──────────────────────────────────────────────

it('module:make-notification produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-notification', ['module' => $this->moduleName, 'name' => 'ArticleReadyNotification'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Notifications/ArticleReadyNotification.php',
        "namespace Modules\\{$this->moduleName}\\Notifications"
    );
});

// ──────────────────────────────────────────────
// module:make-policy
// ──────────────────────────────────────────────

it('module:make-policy produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-policy', ['module' => $this->moduleName, 'name' => 'ArticlePolicy'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Policies/ArticlePolicy.php',
        "namespace Modules\\{$this->moduleName}\\Policies"
    );
});

// ──────────────────────────────────────────────
// module:make-rule
// ──────────────────────────────────────────────

it('module:make-rule produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-rule', ['module' => $this->moduleName, 'name' => 'UniqueArticleTitle'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Rules/UniqueArticleTitle.php',
        "namespace Modules\\{$this->moduleName}\\Rules"
    );
});

// ──────────────────────────────────────────────
// module:make-scope
// ──────────────────────────────────────────────

it('module:make-scope produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-scope', ['module' => $this->moduleName, 'name' => 'PublishedScope'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Models/Scopes/PublishedScope.php',
        "namespace Modules\\{$this->moduleName}\\Models\\Scopes"
    );
});

// ──────────────────────────────────────────────
// module:make-observer
// ──────────────────────────────────────────────

it('module:make-observer produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-observer', ['module' => $this->moduleName, 'name' => 'ArticleObserver'])
        ->assertExitCode(0);

    assertGenerated(
        $this->modulePath . '/src/Observers/ArticleObserver.php',
        "namespace Modules\\{$this->moduleName}\\Observers"
    );
});
