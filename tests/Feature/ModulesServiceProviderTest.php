<?php

use ErlandMuchasaj\Modules\ModulesServiceProvider;
use ErlandMuchasaj\Modules\Support\ModuleCacheManager;
use ErlandMuchasaj\Modules\Support\SeedOrchestrator;

// ModulesServiceProvider is already registered by TestCase via getPackageProviders().

it('SeedOrchestrator is bound as a singleton', function () {
    $a = app(SeedOrchestrator::class);
    $b = app(SeedOrchestrator::class);

    expect($a)->toBe($b);
});

it("the 'modules' abstract resolves to a ModuleCacheManager instance", function () {
    $resolved = app('modules');

    expect($resolved)->toBeInstanceOf(ModuleCacheManager::class);
});

it("the 'modules' abstract is a singleton", function () {
    $a = app('modules');
    $b = app('modules');

    expect($a)->toBe($b);
});

it('default modules config is merged and accessible', function () {
    expect(config('modules'))->toBeArray()
        ->and(config('modules'))->toHaveKey('folder');
});

it('isSeedingCommand returns true for seeding commands and false for others', function () {
    $provider = new ModulesServiceProvider(app());

    $ref = new ReflectionMethod($provider, 'isSeedingCommand');
    $ref->setAccessible(true);

    expect($ref->invoke($provider, 'db:seed'))->toBeTrue()
        ->and($ref->invoke($provider, 'migrate:fresh'))->toBeTrue()
        ->and($ref->invoke($provider, 'migrate:refresh'))->toBeTrue()
        ->and($ref->invoke($provider, 'some:other'))->toBeFalse()
        ->and($ref->invoke($provider, null))->toBeFalse();
});
