<?php
declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Str;

/**
 * Optional Filament panel plugin (Filament 3, 4 and 5).
 *
 * Discovers the Filament resources, pages, widgets and clusters every module ships under
 * "src/Filament/{Resources,Pages,Widgets,Clusters}" and registers them on the panel.
 *
 * This class is only loaded when you reference it, so Filament stays an optional dependency.
 *
 * @example
 * $panel->plugin(ModulesPlugin::make());
 * $panel->plugin(ModulesPlugin::make()->only(['Blog', 'Shop']));
 * $panel->plugin(ModulesPlugin::make()->directory('Filament/Admin'));
 */
class ModulesPlugin implements Plugin
{
    /**
     * Modules to discover (studly names). Null means every module.
     *
     * @var array<int, string>|null
     */
    protected ?array $only = null;

    /**
     * Modules to skip (studly names).
     *
     * @var array<int, string>
     */
    protected array $except = [];

    /**
     * Directory, relative to the module "src" folder, holding the Filament classes.
     */
    protected string $directory = 'Filament';

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'modules';
    }

    /**
     * Limit discovery to the given modules.
     *
     * @param  array<int, string>  $modules
     */
    public function only(array $modules): static
    {
        $this->only = array_map(static fn (string $module): string => Str::studly($module), array_values($modules));

        return $this;
    }

    /**
     * Exclude the given modules from discovery.
     *
     * @param  array<int, string>  $modules
     */
    public function except(array $modules): static
    {
        $this->except = array_map(static fn (string $module): string => Str::studly($module), array_values($modules));

        return $this;
    }

    /**
     * Change the directory (relative to "src") that is scanned, e.g. "Filament/Admin" for a per-panel layout.
     */
    public function directory(string $directory): static
    {
        $this->directory = trim(str_replace('\\', '/', $directory), '/') ?: 'Filament';

        return $this;
    }

    public function register(Panel $panel): void
    {
        $subNamespace = str_replace('/', '\\', $this->directory);

        foreach ($this->modules() as $module => $modulePath) {
            $path = $modulePath.'/src/'.$this->directory;

            if (! is_dir($path)) {
                continue;
            }

            $namespace = $this->rootNamespace().'\\'.$module.'\\'.$subNamespace;

            if (is_dir($path.'/Resources')) {
                $panel->discoverResources(in: $path.'/Resources', for: $namespace.'\\Resources');
            }

            if (is_dir($path.'/Pages')) {
                $panel->discoverPages(in: $path.'/Pages', for: $namespace.'\\Pages');
            }

            if (is_dir($path.'/Widgets')) {
                $panel->discoverWidgets(in: $path.'/Widgets', for: $namespace.'\\Widgets');
            }

            if (is_dir($path.'/Clusters') && method_exists($panel, 'discoverClusters')) {
                $panel->discoverClusters(in: $path.'/Clusters', for: $namespace.'\\Clusters');
            }
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Get the modules to discover, as [StudlyName => absolute path].
     *
     * @return array<string, string>
     */
    protected function modules(): array
    {
        $folder = config('modules.folder') ?: config('modules.base', 'modules');
        $folder = trim((string) $folder, '/\\') ?: 'modules';

        $directories = glob(base_path($folder).'/*', GLOB_ONLYDIR) ?: [];

        $modules = [];

        foreach ($directories as $directory) {
            $module = basename($directory);

            if ($this->only !== null && ! in_array($module, $this->only, true)) {
                continue;
            }

            if (in_array($module, $this->except, true)) {
                continue;
            }

            $modules[$module] = str_replace('\\', '/', $directory);
        }

        return $modules;
    }

    /**
     * Get the root namespace shared by all modules.
     */
    protected function rootNamespace(): string
    {
        return trim((string) config('modules.namespace', 'Modules'), '\\') ?: 'Modules';
    }
}
