<?php

namespace Acme\Billing\Providers {
    use ErlandMuchasaj\Modules\Providers\BaseAppServiceProvider;

    class AppServiceProvider extends BaseAppServiceProvider
    {
        protected string $module = 'Billing';

        public function exposedModuleNamespace(): string
        {
            return $this->moduleNamespace();
        }
    }
}

namespace Modules\Invoicing\Providers {
    use ErlandMuchasaj\Modules\Providers\BaseAppServiceProvider;

    class AppServiceProvider extends BaseAppServiceProvider
    {
        protected string $module = 'Invoicing';

        public function exposedModuleNamespace(): string
        {
            return $this->moduleNamespace();
        }
    }
}

namespace {
    use Acme\Billing\Providers\AppServiceProvider as BillingProvider;
    use ErlandMuchasaj\Modules\Filament\ModulesPlugin;
    use ErlandMuchasaj\Modules\Providers\BaseAppServiceProvider;
    use Illuminate\Support\Facades\File;
    use Modules\Invoicing\Providers\AppServiceProvider as InvoicingProvider;

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    function makeIntegrationProvider(string $module): BaseAppServiceProvider
    {
        return new class(app(), $module) extends BaseAppServiceProvider
        {
            public function __construct($app, string $mod)
            {
                parent::__construct($app);
                $this->module = $mod;
            }

            public function exposedModuleNamespace(): string
            {
                return $this->moduleNamespace();
            }
        };
    }

    function writeLivewireCounter(string $module): string
    {
        $dir = base_path("modules/$module/src/Livewire");
        File::ensureDirectoryExists($dir);

        $file = $dir.'/Counter.php';
        File::put($file, implode("\n", [
            '<?php',
            '',
            "namespace Modules\\$module\\Livewire;",
            '',
            'class Counter extends \Livewire\Component',
            '{',
            '    public int $count = 0;',
            '',
            '    public function render()',
            '    {',
            "        return '<div>{{ \$count }}</div>';",
            '    }',
            '}',
            '',
        ]));

        return $file;
    }

    afterEach(function () {
        foreach (['LwBlog', 'LwShop', 'FilBlog', 'FilShop'] as $module) {
            File::deleteDirectory(base_path('modules/'.$module));
        }
    });

    // ──────────────────────────────────────────────
    // moduleNamespace
    // ──────────────────────────────────────────────

    it('moduleNamespace is derived from the provider class', function () {
        expect((new InvoicingProvider(app()))->exposedModuleNamespace())->toBe('Modules\\Invoicing');
    });

    it('moduleNamespace supports a custom root namespace', function () {
        expect((new BillingProvider(app()))->exposedModuleNamespace())->toBe('Acme\\Billing');
    });

    it('moduleNamespace falls back to Modules\\<Module> for anonymous providers', function () {
        expect(makeIntegrationProvider('LwBlog')->exposedModuleNamespace())->toBe('Modules\\LwBlog');
    });

    // ──────────────────────────────────────────────
    // register() without a module config file
    // ──────────────────────────────────────────────

    it('a module without config/config.php registers and boots without throwing', function () {
        expect(is_file(base_path('modules/LwShop/config/config.php')))->toBeFalse();

        // Any exception thrown here fails the test.
        $provider = app()->register(makeIntegrationProvider('LwShop'));

        expect($provider)->toBeInstanceOf(BaseAppServiceProvider::class)
            ->and(config('modules.lw_shop.config'))->toBeNull();
    });

    // ──────────────────────────────────────────────
    // Livewire
    // ──────────────────────────────────────────────

    it('a module shipping Livewire components boots fine when Livewire is not installed', function () {
        File::ensureDirectoryExists(base_path('modules/LwShop/src/Livewire'));

        // Any exception thrown here fails the test.
        $provider = app()->register(makeIntegrationProvider('LwShop'));

        expect($provider)->toBeInstanceOf(BaseAppServiceProvider::class);
    })->skip(fn () => class_exists(\Livewire\LivewireServiceProvider::class), 'Livewire is installed.');

    it('registers module Livewire components under the module alias', function () {
        require_once writeLivewireCounter('LwBlog');

        app()->register(\Livewire\LivewireServiceProvider::class);
        app()->register(makeIntegrationProvider('LwBlog'));

        $component = app('livewire')->new('lw_blog::counter');

        expect($component)->toBeInstanceOf(\Modules\LwBlog\Livewire\Counter::class)
            ->and($component->getName())->toBe('lw_blog::counter');
    })->skip(fn () => ! class_exists(\Livewire\LivewireServiceProvider::class), 'Livewire is not installed.');

    it('resolves the name Livewire generates from a module component class', function () {
        require_once writeLivewireCounter('LwBlog');

        app()->register(\Livewire\LivewireServiceProvider::class);
        app()->register(makeIntegrationProvider('LwBlog'));

        $name = app('livewire')->new(\Modules\LwBlog\Livewire\Counter::class)->getName();

        expect(app('livewire')->new($name))->toBeInstanceOf(\Modules\LwBlog\Livewire\Counter::class);
    })->skip(fn () => ! class_exists(\Livewire\LivewireServiceProvider::class), 'Livewire is not installed.');

    it('renders and updates a module Livewire component through the module alias', function () {
        require_once writeLivewireCounter('LwBlog');

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        app()->register(\Livewire\LivewireServiceProvider::class);
        app()->register(makeIntegrationProvider('LwBlog'));

        \Livewire\Livewire::test('lw_blog::counter')
            ->assertSee('0')
            ->set('count', 5)
            ->assertSee('5');
    })->skip(fn () => ! class_exists(\Livewire\LivewireServiceProvider::class), 'Livewire is not installed.');

    it('does not touch Livewire when the module ships no Livewire components', function () {
        app()->register(\Livewire\LivewireServiceProvider::class);
        app()->register(makeIntegrationProvider('LwShop'));

        expect(fn () => app('livewire')->new('lw_shop::counter'))->toThrow(Exception::class);
    })->skip(fn () => ! class_exists(\Livewire\LivewireServiceProvider::class), 'Livewire is not installed.');

    // ──────────────────────────────────────────────
    // Filament
    // ──────────────────────────────────────────────

    it('the Filament plugin discovers module resources, pages and widgets', function () {
        foreach (['Resources', 'Pages', 'Widgets'] as $directory) {
            File::ensureDirectoryExists(base_path("modules/FilBlog/src/Filament/$directory"));
        }
        File::ensureDirectoryExists(base_path('modules/FilShop/src/Filament/Resources'));

        $panel = \Filament\Panel::make()->id('modules-test');

        $plugin = ModulesPlugin::make()->except(['FilShop']);
        $plugin->register($panel);

        $normalize = static fn (array $paths): array => array_map(
            static fn (string $path): string => str_replace('\\', '/', $path),
            $paths
        );

        $expected = str_replace('\\', '/', base_path('modules/FilBlog/src/Filament'));

        expect($plugin->getId())->toBe('modules')
            ->and($normalize($panel->getResourceDirectories()))->toContain($expected.'/Resources')
            ->and($normalize($panel->getResourceDirectories()))->not->toContain(str_replace('\\', '/', base_path('modules/FilShop/src/Filament/Resources')))
            ->and($panel->getResourceNamespaces())->toContain('Modules\\FilBlog\\Filament\\Resources')
            ->and($normalize($panel->getPageDirectories()))->toContain($expected.'/Pages')
            ->and($panel->getPageNamespaces())->toContain('Modules\\FilBlog\\Filament\\Pages')
            ->and($normalize($panel->getWidgetDirectories()))->toContain($expected.'/Widgets')
            ->and($panel->getWidgetNamespaces())->toContain('Modules\\FilBlog\\Filament\\Widgets');
    })->skip(fn () => ! class_exists(\Filament\Panel::class), 'Filament is not installed.');
}
