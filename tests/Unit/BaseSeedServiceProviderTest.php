<?php

use ErlandMuchasaj\Modules\Providers\BaseSeedServiceProvider;
use ErlandMuchasaj\Modules\Support\SeedOrchestrator;

function makeSeedProvider(string $namespace = '', int $priority = 100, array $dependencies = []): BaseSeedServiceProvider
{
    return new class(app(), $namespace, $priority, $dependencies) extends BaseSeedServiceProvider {
        public function __construct(
            $app,
            string $ns,
            int    $pri,
            array  $deps,
        ) {
            parent::__construct($app);
            $this->namespace    = $ns;
            $this->priority     = $pri;
            $this->dependencies = $deps;
        }
    };
}

beforeEach(function () {
    app()->forgetInstance(SeedOrchestrator::class);
    app()->singleton(SeedOrchestrator::class, fn () => new SeedOrchestrator());
});

// ──────────────────────────────────────────────
// registerSeeder
// ──────────────────────────────────────────────

it('does not register a seeder when namespace is empty', function () {
    $provider = makeSeedProvider(namespace: '');

    $ref = new ReflectionMethod($provider, 'registerSeeder');
    $ref->setAccessible(true);
    $ref->invoke($provider);

    $orchestrator = app(SeedOrchestrator::class);

    expect($orchestrator->getSeeders())->toBe([]);
});

it('registers the seeder with the orchestrator when namespace is set', function () {
    $provider = makeSeedProvider(
        namespace: 'Modules\Alpha\Database\Seeders\DatabaseSeeder',
        priority:  150,
    );

    $ref = new ReflectionMethod($provider, 'registerSeeder');
    $ref->setAccessible(true);
    $ref->invoke($provider);

    $orchestrator = app(SeedOrchestrator::class);
    $seeders      = $orchestrator->getSeeders();

    expect($seeders)->toHaveKey('Modules\Alpha\Database\Seeders\DatabaseSeeder')
        ->and($seeders['Modules\Alpha\Database\Seeders\DatabaseSeeder']['priority'])->toBe(150);
});

it('forwards dependencies to the orchestrator', function () {
    $provider = makeSeedProvider(
        namespace:    'Modules\Beta\Database\Seeders\DatabaseSeeder',
        priority:     100,
        dependencies: ['Modules\Alpha\Database\Seeders\DatabaseSeeder'],
    );

    $ref = new ReflectionMethod($provider, 'registerSeeder');
    $ref->setAccessible(true);
    $ref->invoke($provider);

    $orchestrator = app(SeedOrchestrator::class);
    $entry        = $orchestrator->getSeeders()['Modules\Beta\Database\Seeders\DatabaseSeeder'];

    expect($entry['dependencies'])->toContain('Modules\Alpha\Database\Seeders\DatabaseSeeder');
});
