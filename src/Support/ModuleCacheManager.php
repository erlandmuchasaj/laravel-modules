<?php
declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Module Cache Manager
 * Handles caching of module configurations, routes, and other data to improve performance in production.
 */
class ModuleCacheManager
{
    /**
     * Cache key prefix.
     * @var string
     */
    protected string $prefix = 'modules';

    /**
     * Default cache TTL (1 hour).
     * @var int
     */
    protected int $ttl = 3600;

    /**
     * Resolve the configured modules directory.
     *
     * Supports both the current `modules.folder` key and the legacy
     * `modules.base` key for backwards compatibility.
     */
    protected function modulesDirectory(): string
    {
        $folder = config('modules.folder') ?: config('modules.base', 'modules');

        return trim((string) $folder, '/\\') ?: 'modules';
    }

    /**
     * Get module configuration from the cache or load it.
     * @param string $module
     * @return array|null
     */
    public function getModuleConfig(string $module): ?array
    {
        $key = $this->getCacheKey("config.{$module}");

        return Cache::remember($key, $this->ttl, function () use ($module) {
            $configPath = $this->getModuleConfigPath($module);

            if (!file_exists($configPath)) {
                return null;
            }

            return include $configPath;
        });
    }

    /**
     * Get all registered modules.
     * @return array
     */
    public function getRegisteredModules(): array
    {
        $key = $this->getCacheKey('registered');

        return Cache::remember($key, $this->ttl, function () {
            return $this->discoverModules();
        });
    }

    /**
     * Cache module routes manifest.
     */
    public function cacheRoutesManifest(array $modules): void
    {
        $manifest = [];

        foreach ($modules as $module) {
            $manifest[$module] = [
                'web' => $this->getModuleRoutePath($module, 'web.php'),
                'api' => $this->getModuleRoutePath($module, 'api.php'),
                'channels' => $this->getModuleRoutePath($module, 'channels.php'),
            ];
        }

        Cache::put(
            $this->getCacheKey('routes.manifest'),
            $manifest,
            $this->ttl
        );
    }

    /**
     * Get cached routes manifest.
     */
    public function getRoutesManifest(): ?array
    {
        return Cache::get($this->getCacheKey('routes.manifest'));
    }

    /**
     * Cache module migrations list.
     */
    public function cacheMigrations(string $module): array
    {
        $key = $this->getCacheKey("migrations.{$module}");

        return Cache::remember($key, $this->ttl, function () use ($module) {
            $path = $this->getModuleMigrationsPath($module);

            if (!is_dir($path)) {
                return [];
            }

            return collect(File::files($path))
                ->map(fn($file) => $file->getFilename())
                ->sort()
                ->values()
                ->toArray();
        });
    }

    /**
     * Cache module views.
     */
    public function cacheViews(string $module): array
    {
        $key = $this->getCacheKey("views.{$module}");

        return Cache::remember($key, $this->ttl, function () use ($module) {
            $path = $this->getModuleViewsPath($module);

            if (!is_dir($path)) {
                return [];
            }

            return collect(File::allFiles($path))
                ->map(function ($file) use ($path) {
                    $relativePath = str_replace($path . '/', '', $file->getPathname());
                    return str_replace(['/', '.blade.php', '.php'], ['.', '', ''], $relativePath);
                })
                ->values()
                ->toArray();
        });
    }

    /**
     * Clear all module caches.
     */
    public function clearAll(): void
    {
        if (Cache::supportsTags()) {
            Cache::tags($this->prefix)->flush();

            return;
        }

        $modules = $this->getRegisteredModules();

        Cache::forget($this->getCacheKey('registered'));
        Cache::forget($this->getCacheKey('routes.manifest'));

        foreach ($modules as $module) {
            $this->clearModule($module);
        }
    }

    /**
     * Clear cache for specific module.
     */
    public function clearModule(string $module): void
    {
        Cache::forget($this->getCacheKey("config.{$module}"));
        Cache::forget($this->getCacheKey("migrations.{$module}"));
        Cache::forget($this->getCacheKey("views.{$module}"));
    }

    /**
     * Warm up all module caches.
     */
    public function warmUp(): void
    {
        $modules = $this->discoverModules();

        foreach ($modules as $module) {
            $this->getModuleConfig($module);
            $this->cacheMigrations($module);
            $this->cacheViews($module);
        }

        $this->cacheRoutesManifest($modules);
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        $modules = $this->getRegisteredModules();

        $stats = [
            'total_modules' => count($modules),
            'cached_configs' => 0,
            'cached_migrations' => 0,
            'cached_views' => 0,
        ];

        foreach ($modules as $module) {
            if (Cache::has($this->getCacheKey("config.{$module}"))) {
                $stats['cached_configs']++;
            }
            if (Cache::has($this->getCacheKey("migrations.{$module}"))) {
                $stats['cached_migrations']++;
            }
            if (Cache::has($this->getCacheKey("views.{$module}"))) {
                $stats['cached_views']++;
            }
        }

        return $stats;
    }

    /**
     * Discover all modules in the modules directory.
     */
    protected function discoverModules(): array
    {
        $basePath = base_path($this->modulesDirectory());

        if (!is_dir($basePath)) {
            return [];
        }

        return collect(File::directories($basePath))
            ->map(fn($dir) => basename($dir))
            ->filter(function ($module) use ($basePath) {
                // Check if the module has a valid service provider
                $providerPath = $basePath . "/{$module}/src/Providers/AppServiceProvider.php";
                return file_exists($providerPath);
            })
            ->values()
            ->toArray();
    }

    /**
     * Get cache key with prefix.
     */
    protected function getCacheKey(string $key): string
    {
        return "{$this->prefix}.{$key}";
    }

    /**
     * Get module config-path.
     */
    protected function getModuleConfigPath(string $module): string
    {
        return base_path($this->modulesDirectory() . "/{$module}/config/config.php");
    }

    /**
     * Get a module route path.
     */
    protected function getModuleRoutePath(string $module, string $file): string
    {
        return base_path($this->modulesDirectory() . "/{$module}/routes/{$file}");
    }

    /**
     * Get module migrations path.
     */
    protected function getModuleMigrationsPath(string $module): string
    {
        return base_path($this->modulesDirectory() . "/{$module}/database/migrations");
    }

    /**
     * Get module views-path.
     */
    protected function getModuleViewsPath(string $module): string
    {
        return base_path($this->modulesDirectory() . "/{$module}/resources/views");
    }

}
