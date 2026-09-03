<?php

namespace ErlandMuchasaj\Modules\Tests;

use ErlandMuchasaj\Modules\ModulesServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ModulesServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('modules.folder', 'modules');
        $app['config']->set('modules.discovery.cache', false);
        $app['config']->set('modules.seeds.enabled', true);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('mail.default', 'array');
    }
}
