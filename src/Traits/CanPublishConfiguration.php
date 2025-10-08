<?php
declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Traits;

use Illuminate\Support\Str;

/**
 * Trait to publish and register module configuration files.
 */
trait CanPublishConfiguration
{
    /**
     * The root namespace to assume when generating URLs to actions.
     * @var string
     */
    protected string $base = 'modules';

    /**
     * Publish the given configuration file name (without extension) and the given module.
     */
    public function publishConfig(string $module, string $fileName): void
    {
        if (app()->environment() === 'testing') {
            return;
        }
        $this->bootConfig($module, $fileName);
        $this->registerConfig($module, $fileName);
    }

    /**
     * Boot the config for publishing.
     */
    protected function bootConfig(string $module, string $fileName): void
    {
        if (app()->runningInConsole()) {
            $this->publishes([
                $this->getModuleConfigFilePath($module, $fileName) => config_path(Str::lower($this->base.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.$fileName).'.php'),
            ], 'config');
        }
    }

    /**
     * Merge config of module to Laravel configuration files.
     */
    protected function registerConfig(string $module, string $fileName): void
    {
        $this->mergeConfigFrom(
            $this->getModuleConfigFilePath($module, $fileName),
            Str::lower("$this->base.$module.$fileName")
        );
    }

    /**
     * Get path of the give file name in the given module
     */
    private function getModuleConfigFilePath(string $module, string $file): string
    {
        return $this->getModulePath($module).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR."$file.php";
    }

    private function getModulePath(string $module): string
    {
        return base_path($this->base.DIRECTORY_SEPARATOR.Str::studly($module));
    }
}
