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
     * Publishing (vendor:publish) is skipped in the testing environment, but config merging
     * always runs so that module config values are available in tests.
     */
    public function publishConfig(string $module, string $fileName): void
    {
        if (! app()->environment('testing')) {
            $this->bootConfig($module, $fileName);
        }
        $this->registerConfig($module, $fileName);
    }

    /**
     * Boot the config for publishing.
     */
    protected function bootConfig(string $module, string $fileName): void
    {
        if (app()->runningInConsole()) {
            // Use forward slashes for cross-platform config path compatibility
            $publishTarget = config_path(Str::lower($this->base.'/'.$module.'/'.$fileName).'.php');
            $this->publishes([
                $this->getModuleConfigFilePath($module, $fileName) => $publishTarget,
            ], 'config');
        }
    }

    /**
     * Merge config of the module to Laravel configuration files.
     */
    protected function registerConfig(string $module, string $fileName): void
    {
        $this->mergeConfigFrom(
            $this->getModuleConfigFilePath($module, $fileName),
            Str::lower("$this->base.$module.$fileName")
        );
    }

    /**
     * Get the path of the give file name in the given module
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
