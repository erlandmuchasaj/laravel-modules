<?php

namespace ErlandMuchasaj\Modules\Traits;

use Illuminate\Support\Str;

trait CanPublishConfiguration
{
    /**
     * The root namespace to assume when generating URLs to actions.
     */
    protected string $base = 'modules';

    /**
     * Publish the given configuration file name (without extension) and the given module
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
     * Publish the given configuration file name (without extension) and the given module
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
     * Merge config og module to laravel configuration files
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
        return base_path('modules'.DIRECTORY_SEPARATOR.Str::studly($module));
    }
}
